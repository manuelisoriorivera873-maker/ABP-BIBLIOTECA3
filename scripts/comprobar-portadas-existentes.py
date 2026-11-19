"""Revalida las imágenes existentes, sin modificar la base de datos."""
import json
import pathlib
import time
import urllib.request

CACHE = pathlib.Path(__file__).resolve().parents[1] / "storage/app/private/ampliacion"
inventario = json.loads((CACHE / "inventario.json").read_text(encoding="utf-8"))
resultado = []
for libro in inventario:
    url = libro["ficha"].get("portada_url")
    if not url:
        continue
    if "covers.openlibrary.org" in url:
        url += "?default=false"
    try:
        time.sleep(1.1)
        req = urllib.request.Request(url, headers={"User-Agent": "SAIDE-Biblioteca/1.0 (bounded existing-cover verification)"})
        with urllib.request.urlopen(req, timeout=25) as respuesta:
            mime = respuesta.headers.get("Content-Type", "")
            contenido = respuesta.read(2_000_000)
        if not mime.startswith("image/") or len(contenido) < 1500:
            raise ValueError("Imagen vacía o respuesta no gráfica")
        archivo = CACHE / ("existente-" + str(libro["id_libro"]) + ".jpg")
        archivo.write_bytes(contenido)
        resultado.append(dict(titulo=libro["titulo"], isbn=libro["isbn"], url=url, archivo=str(archivo), valida=True))
    except Exception as error:
        resultado.append(dict(titulo=libro["titulo"], isbn=libro["isbn"], valida=False, error=str(error)))
    (CACHE / "portadas-existentes.json").write_text(json.dumps(resultado, ensure_ascii=False, indent=2), encoding="utf-8")
