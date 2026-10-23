"""Generate Joomla's stable update feed from an already-built release ZIP."""
import argparse
import hashlib
from pathlib import Path
import re
import xml.etree.ElementTree as ET
import zipfile


def generate(package, tag):
    if not re.fullmatch(r'\d+\.\d+\.\d+', tag):
        raise ValueError('A stable numeric tag such as 2.0.6 is required')
    package = Path(package)
    if package.name != f'plg_system_typographe-{tag}.zip':
        raise ValueError('Package filename must match the release tag')
    with zipfile.ZipFile(package) as archive:
        manifest = ET.fromstring(archive.read('typographe.xml'))
        if manifest.findtext('version') != tag or manifest.get('group') != 'system':
            raise ValueError('Package manifest does not match this release')
        if 'dependencies/autoload.php' not in archive.namelist():
            raise ValueError('Use the installable package, not the source archive')
        if manifest.findtext('updateservers/server') != 'https://github.com/YGomiero/typographe/releases/latest/download/update.xml':
            raise ValueError('The package must keep the update server registered')
    root = ET.Element('updates')
    update = ET.SubElement(root, 'update')
    for key, value in [('name', 'Typographe'), ('description', 'Editing-time typography for Joomla 6'),
                       ('element', 'typographe'), ('type', 'plugin'), ('folder', 'system'),
                       ('client', 'site'), ('version', tag)]:
        ET.SubElement(update, key).text = value
    url = f'https://github.com/YGomiero/typographe/releases/download/{tag}/{package.name}'
    ET.SubElement(ET.SubElement(update, 'downloads'), 'downloadurl', {'type':'full', 'format':'zip'}).text = url
    ET.SubElement(update, 'infourl', {'title':'Typographe release notes'}).text = f'https://github.com/YGomiero/typographe/releases/tag/{tag}'
    ET.SubElement(ET.SubElement(update, 'tags'), 'tag').text = 'stable'
    ET.SubElement(update, 'targetplatform', {'name':'joomla', 'version':r'6\.[0-9]+'})
    ET.SubElement(update, 'php_minimum').text = '8.3.0'
    ET.SubElement(update, 'sha256').text = hashlib.sha256(package.read_bytes()).hexdigest()
    ET.indent(root)
    return ET.tostring(root, encoding='utf-8', xml_declaration=True)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('package')
    parser.add_argument('tag')
    parser.add_argument('output')
    args = parser.parse_args()
    Path(args.output).write_bytes(generate(args.package, args.tag))
    print(f'Validated Joomla update feed: {args.output}')
