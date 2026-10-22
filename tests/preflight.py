"""Dependency-light static preflight for the Laravel demo project."""
from pathlib import Path
import json, re, shutil, subprocess, sys, xml.etree.ElementTree as ET
ROOT = Path(__file__).resolve().parents[1]
errors=[]
php=shutil.which('php'); node=shutil.which('node')
if not php: errors.append('PHP CLI is not installed; PHP syntax check skipped.')
else:
    for f in sorted(ROOT.rglob('*.php')):
        if any(part in {'vendor','storage'} for part in f.parts): continue
        p=subprocess.run([php,'-l',str(f)],capture_output=True,text=True)
        if p.returncode: errors.append(f'PHP syntax error: {f.relative_to(ROOT)}\n{p.stdout}{p.stderr}')
json.loads((ROOT/'composer.json').read_text(encoding='utf-8'))
ET.parse(ROOT/'phpunit.xml')
view=(ROOT/'resources/views/elearn.blade.php').read_text(encoding='utf-8')
if 'name="csrf-token" content="{{ csrf_token() }}"' not in view: errors.append('CSRF meta tag missing in Blade view.')
api_paths=set(re.findall(r"fetch\(['\"](/api/[^'\"]+)",view))
routes=(ROOT/'routes/web.php').read_text(encoding='utf-8')
for path in sorted(api_paths):
    if path not in routes: errors.append(f'No literal route definition found for frontend call {path}')
if node:
    scripts=re.findall(r'<script(?:\s[^>]*)?>(.*?)</script\s*>',view,re.I|re.S)
    for i,script in enumerate(scripts,1):
        tmp=ROOT/'storage'/'app'/f'preflight-script-{i}.js'; tmp.parent.mkdir(parents=True,exist_ok=True); tmp.write_text(script,encoding='utf-8')
        p=subprocess.run([node,'--check',str(tmp)],capture_output=True,text=True)
        tmp.unlink(missing_ok=True)
        if p.returncode: errors.append(f'JavaScript syntax error in script block {i}: {p.stdout}{p.stderr}')
else: print('NOTE: Node.js unavailable; embedded JavaScript syntax check skipped.')
view_test=subprocess.run([sys.executable, str(ROOT/'tests/check_reporting_views.py')],capture_output=True,text=True)
if view_test.returncode: errors.append('Reporting view SQL check failed: '+view_test.stdout+view_test.stderr)
for required in ['users','courses','enrollments','lessons','assignments','submissions','grades']:
    if not any(required in f.read_text(encoding='utf-8') for f in (ROOT/'database/migrations').glob('*.php')): errors.append(f'Missing migration evidence for table: {required}')
if errors:
    print('\n'.join(errors)); sys.exit(1)
print(f'PASS: PHP syntax, JSON/XML configs, CSRF wiring, {len(api_paths)} frontend API paths and JS syntax checked.')
print(view_test.stdout.strip())
print('NOTE: Static preflight does not replace Composer install, PHPUnit, or MySQL migration/integration testing.')
