import cairo, urllib, Image, MySQLdb, sys
sys.path.append("/var/www")
from cupid_config import *
from cStringIO import StringIO

surface = cairo.ImageSurface (cairo.FORMAT_ARGB32, 102, 102)
ctx = cairo.Context (surface)
ctx.scale (1, 1)
defImage = "../../images/likebright-square.png"

def fetchImg(url):
	theimg  = StringIO()
	thedata = StringIO()

	im = urllib.urlopen(url)
	theimg.write(im.read())
	theimg.seek(0)

	im = Image.open(theimg)
	im.save(thedata, format="png")
	thedata.seek(0)
	return thedata

def drawBlock(id, x, y):
	image = cairo.ImageSurface.create_from_png (id=="0" and defImage or fetchImg("http://graph.facebook.com/"+id+"/picture"))
	ctx.set_source_surface (image, x, y)
	ctx.paint()

if len(sys.argv)==2:
	key = sys.argv[1]
	ids = sys.argv[1].split(",")
	drawBlock(ids[0], 0, 0)
	drawBlock(ids[1], 52, 0)
	drawBlock(ids[2], 0, 52)
	drawBlock(ids[3], 52, 52)
		
	thedata = StringIO()
	surface.write_to_png (thedata)

	db = MySQLdb.connect(db_ip, db_user, db_pass, db_name)
	c = db.cursor()

c.execute("REPLACE INTO cupidImage (uid, image) VALUES (%s, %s)", (key, thedata.getvalue()))