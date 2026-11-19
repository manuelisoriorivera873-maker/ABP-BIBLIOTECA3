"""Comprueba una imagen por edición preseleccionada; copia privada para revisión."""
import json
import pathlib
import time
import urllib.request

ROOT = pathlib.Path(__file__).resolve().parents[1]
CACHE = ROOT / "storage/app/private/ampliacion"

if __name__ == "__main__":
    datos = json.loads((CACHE / "seleccion-exacta.json").read_text(encoding="utf-8"))
    resultado = []
    for i, dato in enumerate(datos):
        cubierta = next((c for c in dato["edicion"].get("covers", []) if c > 0), None)
        if not cubierta:
            resultado.append(dict(indice=i, titulo=dato["objetivo"], valida=False))
            continue
        archivo = CACHE / ("portada-" + str(cubierta) + ".jpg")
        url = f"https://covers.openlibrary.org/b/id/{cubierta}-M.jpg?default=false"
        try:
            if not archivo.exists():
                time.sleep(1.1)
                req = urllib.request.Request(url, headers={"User-Agent": "SAIDE-Biblioteca/1.0 (bounded cover verification)"})
                with urllib.request.urlopen(req, timeout=30) as respuesta:
                    if not respuesta.headers.get("Content-Type", "").startswith("image/"):
                        raise ValueError("No es una imagen")
                    contenido = respuesta.read(2_000_000)
                if len(contenido) < 1500:
                    raise ValueError("Miniatura vacía o demasiado pequeña")
                archivo.write_bytes(contenido)
            resultado.append(dict(indice=i, titulo=dato["objetivo"], valida=True, portada=url, archivo=str(archivo)))
        except Exception as error:
            resultado.append(dict(indice=i, titulo=dato["objetivo"], valida=False, error=str(error)))
        (CACHE / "portadas.json").write_text(json.dumps(resultado, ensure_ascii=False, indent=2), encoding="utf-8")
