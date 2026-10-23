import importlib.util
from pathlib import Path
import tempfile
import unittest
import xml.etree.ElementTree as ET
import zipfile
import hashlib
spec=importlib.util.spec_from_file_location('feed',Path(__file__).parents[1]/'tools/update-feed.py')
feed=importlib.util.module_from_spec(spec);spec.loader.exec_module(feed)
class FeedTest(unittest.TestCase):
    def package(self,folder,version='2.0.6',deps=True):
        p=Path(folder)/'plg_system_typographe-2.0.6.zip'
        with zipfile.ZipFile(p,'w') as z:
            z.writestr('typographe.xml',f'<extension group="system"><version>{version}</version><updateservers><server>https://github.com/YGomiero/typographe/releases/latest/download/update.xml</server></updateservers></extension>')
            if deps:z.writestr('dependencies/autoload.php','php')
        return p
    def test_valid_feed(self):
        with tempfile.TemporaryDirectory() as d:
            p=self.package(d);root=ET.fromstring(feed.generate(p,'2.0.6'));u=root.find('update')
            self.assertEqual(u.findtext('sha256'),hashlib.sha256(p.read_bytes()).hexdigest())
            self.assertEqual(u.findtext('folder'),'system');self.assertEqual(u.findtext('client'),'site')
            self.assertIn('/2.0.6/plg_system_typographe-2.0.6.zip',u.findtext('downloads/downloadurl'))
    def test_mismatched_version(self):
        with tempfile.TemporaryDirectory() as d:
            with self.assertRaises(ValueError):feed.generate(self.package(d,'2.0.5'),'2.0.6')
    def test_source_archive_rejected(self):
        with tempfile.TemporaryDirectory() as d:
            with self.assertRaises(ValueError):feed.generate(self.package(d,deps=False),'2.0.6')
    def test_prerelease_rejected(self):
        with self.assertRaises(ValueError):feed.generate('unused','2.0.6-beta')
if __name__=='__main__':unittest.main()
