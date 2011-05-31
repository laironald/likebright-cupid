import cairo, math, urllib, Image, MySQLdb, sys
sys.path.append("/var/www")
from cupid_config import *
from cStringIO import StringIO

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
	
if len(sys.argv)==2:
	key = sys.argv[1]
	ids = sys.argv[1].split("x")
	
	p = 2
	w = 50
	d = int(math.ceil(len(ids)**0.5))
	surface = cairo.ImageSurface (cairo.FORMAT_ARGB32, d*(w+p)-p, d*(w+p)-p)
	ctx = cairo.Context (surface)
	ctx.scale (1, 1)
	
	for i, x in enumerate(ids):
		if x!="":
			image = cairo.ImageSurface.create_from_png (x=="0" and defImage or fetchImg("http://graph.facebook.com/"+x+"/picture"))
			ctx.set_source_surface (image, (i%d)*(w+p), (i/d)*(w+p))
			ctx.paint()
		
	thedata = StringIO()
	surface.write_to_png (thedata)

	db = MySQLdb.connect(db_ip, db_user, db_pass, db_name)
	c = db.cursor()
	c.execute("REPLACE INTO cupidImage (uid, image) VALUES (%s, %s)", (key, thedata.getvalue()))