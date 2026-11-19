"""Contrasta ISBN concretos; solo prepara evidencia privada para revisión."""
import importlib.util
import json
import pathlib

spec = importlib.util.spec_from_file_location("bibliografia", pathlib.Path(__file__).with_name("consultar-bibliografia.py"))
api = importlib.util.module_from_spec(spec)
spec.loader.exec_module(api)

if __name__ == "__main__":
    registros = json.loads((api.CACHE / "seleccion.json").read_text(encoding="utf-8"))
    for titulo, autor, idioma, isbn in json.loads((api.CACHE / "exactos.json").read_text(encoding="utf-8")):
        try:
            edicion = api.consultar("/isbn/" + isbn + ".json")
            obra = api.consultar(edicion["works"][0]["key"] + ".json")
            claves = [a["author"]["key"] for a in obra.get("authors", [])]
            autores = [api.consultar(k + ".json") for k in claves[:2]]
            dato = dict(objetivo=titulo, autor_objetivo=autor, idioma=idioma, obra=obra, edicion=edicion, autores=autores)
            registros = [r for r in registros if r["objetivo"] != titulo] + [dato]
            (api.CACHE / "seleccion-exacta.json").write_text(json.dumps(registros, ensure_ascii=False, indent=2), encoding="utf-8")
            print(titulo, "|", edicion.get("title"), "|", edicion.get("publishers"), "|", edicion.get("publish_date"), "|", edicion.get("physical_format"), flush=True)
        except Exception as error:
            print("PENDIENTE", titulo, type(error).__name__, str(error)[:100], flush=True)
