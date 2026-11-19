import pathlib,urllib.request,zipfile,re,json,hashlib
p=pathlib.Path(__file__).resolve().parents[1]/'storage/app/private/lecturas';p.mkdir(exist_ok=True)
u='https://www.gutenberg.org/ebooks/2000.epub3.images'
f=p/'don-quijote-gutenberg-2000.epub'
if not f.exists():
 with urllib.request.urlopen(urllib.request.Request(u,headers={'User-Agent':'SAIDE-Biblioteca/1.0'}),timeout=60) as r:f.write_bytes(r.read(20000001))
assert f.stat().st_size<=20000000, 'Archivo demasiado grande'
assert hashlib.sha256(f.read_bytes()).hexdigest()=='8ee8c3650d397da45097214f21cff57d2ce8d035dfcd6585e0096e3bb74989fa', 'La fuente cambió; requiere nueva revisión antes de habilitarse'
with zipfile.ZipFile(f) as z:
 assert z.read('mimetype')==b'application/epub+zip'
 for n in z.namelist():
  assert not n.startswith('/') and '..' not in pathlib.PurePosixPath(n).parts
  if n.endswith(('.html','.xhtml','.htm')):
   s=z.read(n).decode('utf-8')
   assert not re.search(r'<script\b|\son[a-z]+\s*=|javascript:',s,re.I),(n,'contenido activo')
 print('EPUB verificado',len(z.namelist()),f.stat().st_size,hashlib.sha256(f.read_bytes()).hexdigest())
