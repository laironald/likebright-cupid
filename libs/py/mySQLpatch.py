import MySQLdb, sys, json
sys.path.append("/var/www")
from cupid_config import *

db = MySQLdb.connect(db_ip, db_user, db_pass, db_name)
c = db.cursor()

## THIS IS FOR UPDATING USERS VOTED AND MATCHED 
if 1==1:
	def getFriends(fid, freq, option):
		db = MySQLdb.connect(db_ip, db_user, db_pass, db_name)
		c = db.cursor()
		if option==1:
			c.execute("UPDATE cupidUser SET matched=matched+%s WHERE uid=%s", (freq, fid,))
		elif option==2:
			c.execute("UPDATE cupidUser SET voted=voted+%s WHERE uid in (SELECT uid FROM cupidFriends WHERE fid=%s)", (freq, fid,))
		c.close()
		db.close()

	c.execute("UPDATE cupidUser SET voted=0, matched=0")
	c.execute("SELECT cid, fid1,fid2 FROM cupidVote")

	usrMatch = {}
	allVotes = {}
	for votes in c.fetchall():
		if votes[0] not in usrMatch:
			usrMatch[votes[0]] = 0
		usrMatch[votes[0]]+=1

		if votes[1] not in allVotes:
			allVotes[votes[1]] = 0
		allVotes[votes[1]]+=1
		
		if votes[2] not in allVotes:
			allVotes[votes[2]] = 0
		allVotes[votes[2]]+=1	
		
	for k,v in usrMatch.items():
		print k,v
		getFriends(k, v, 1)

	for k,v in allVotes.items():
		print k,v
		getFriends(k, v, 2)

	
## THIS IS FOR UPDATING COUNTRY CODES FOR USERS
if 1==1:
	c.execute("UPDATE cupidUser SET country=''")
	c.execute("SELECT uid, json FROM cupidUser")
	for u,j in c.fetchall():
		d = json.loads(j)
		if d["current_location"] != None:
			print u
			c.execute("UPDATE cupidUser SET country=%s WHERE uid=%s", (d["current_location"]["country"], u,))