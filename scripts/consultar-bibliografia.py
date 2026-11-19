"""Consulta acotada, secuencial y reutilizable de los endpoints oficiales.

Los resultados son candidatos privados para revisión humana; no se importan solos.
"""
import json
import pathlib
import time
import urllib.parse
import urllib.request

ROOT = pathlib.Path(__file__).resolve().parents[1]
CACHE = ROOT / "storage/app/private/ampliacion"
CACHE.mkdir(parents=True, exist_ok=True)


def consultar(path):
    archivo = CACHE / (urllib.parse.quote(path, safe="") + ".json")
    if archivo.exists():
        return json.loads(archivo.read_text(encoding="utf-8"))
    time.sleep(1.1)
    request = urllib.request.Request("https://openlibrary.org" + path,
                                     headers={"User-Agent": "SAIDE-Biblioteca/1.0 (bounded bibliographic verification)"})
    with urllib.request.urlopen(request, timeout=40) as respuesta:
        datos = json.load(respuesta)
    archivo.write_text(json.dumps(datos, ensure_ascii=False), encoding="utf-8")
    return datos


if __name__ == "__main__":
    candidatos = json.loads((CACHE / "candidatos.json").read_text(encoding="utf-8"))
    resultado = []
    for titulo, autor, idioma in candidatos:
        try:
            query = urllib.parse.urlencode({"title": titulo, "author": autor, "limit": 2,
                                           "fields": "key,title,author_name,author_key,cover_edition_key"})
            docs = consultar("/search.json?" + query).get("docs", [])
            if not docs:
                print("SIN RESULTADO", titulo, flush=True)
                continue
            doc = docs[0]
            obra = consultar(doc["key"] + ".json")
            ediciones = consultar(doc["key"] + "/editions.json?limit=100").get("entries", [])
            # Solamente una edición con idioma explícito, ISBN-13, editor y portada.
            elegibles = [e for e in ediciones if e.get("isbn_13") and e.get("publishers")
                         and e.get("publish_date") and any(c > 0 for c in e.get("covers", []))
                         and [x["key"] for x in e.get("languages", [])] == ["/languages/" + idioma]
                         and not any(w in (e.get("title", "") + " " + e.get("subtitle", "")).lower()
                                     for w in ["graphic novel", "adapted", "abridged", "study guide", "sparknotes", "summary"])]
            if not elegibles:
                print("SIN EDICIÓN COMPLETA", titulo, flush=True)
                continue
            edicion = elegibles[0]
            autores = [consultar("/authors/" + key + ".json") for key in doc.get("author_key", [])[:2]]
            resultado.append(dict(objetivo=titulo, autor_objetivo=autor, idioma=idioma, consulta=doc,
                                  obra=obra, edicion=edicion, autores=autores))
            (CACHE / "seleccion.json").write_text(json.dumps(resultado, ensure_ascii=False, indent=2), encoding="utf-8")
            print(titulo, "|", edicion.get("title"), "|", edicion.get("isbn_13"), "|", edicion.get("publishers"), "|", edicion.get("publish_date"), flush=True)
        except Exception as error:
            print("PENDIENTE", titulo, type(error).__name__, str(error)[:100], flush=True)
