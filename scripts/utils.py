import sys
import codecs
sys.stdout = codecs.getwriter('utf8')(sys.stdout)

import socket
import urllib2
import urllib
import httplib
from BeautifulSoup import BeautifulSoup,  SoupStrainer
import re
import MySQLdb
import _mysql_exceptions
import dbconf
import feedparser
import time
from urlparse import urlparse
import datetime
import syslog

re_link = re.compile(r'<link ([^>]+(?:text\/xml|application\/atom\+xml|application\/rss\+xml)[^>]+[^>]+)/*>',re.I)
re_href = re.compile(r'''href=['"]*([^"']+)["']''', re.I)
hdr_string = {'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.64 Safari/537.11'}

def post_note(text):
	try:
		url = """
			http://{d}{newpost}?user={post_user}&key={post_key}&text={t}
		""".format(d= dbconf.domain,
					t= urllib.quote_plus(text),
					**dbconf.blogs)
		## TODO: Use timeout parameter instead of
		##		 socket.setdefaulttimeout(timeout)
		urlpost = urllib2.urlopen(url)
		print urlpost.read(100)
		urlpost.close()
	except KeyError:
		return False
	return True


def read_annotation(key):
	try:
		c = DBM.cursor()
		c.execute("SELECT annotation_text FROM annotations WHERE annotation_key = '%s' AND (annotation_expire is null or annotation_expire > now())" % (key,))
		row = c.fetchone()
		c.close()
		if row:
			return row[0]
		else:
			return None
	except Exception as e:
		print "Error in read annotation: " + key + " " + unicode(e)
		syslog.syslog(syslog.LOG_INFO, "Error in read annotaion: " + key + " " + unicode(e))
		return False

def store_annotation(key, text):
	try:
		c = DBM.cursor('update')
		c.execute("REPLACE INTO annotations (annotation_key, annotation_text) VALUES (%s, %s)", (key, text))
		c.close()
		DBM.commit()
		DBM.close('update')
	except Exception as e:
		DBM.close('update')
		print "Error in store annotation: " + key + " " + unicode(e)
		syslog.syslog(syslog.LOG_INFO, "Error in store annotaion: " + key + " " + unicode(e))
		return False

def clean_url(string):
	string = re.sub(r'&amp;', '&', string)
	string = re.sub(r'[<>\r\n\t]|utm_\w+?=[^&]*', '', string) #  Delete common variables  for Analitycs and illegal chars
	string = re.sub(r'&{2,}', '&', string) # Delete duplicates &
	string = re.sub(r'&+$', '', string) # Delete useless & at the end
	string = re.sub(r'\?&+', '?', string) # Delete useless & after ?
	string = re.sub(r'\?&*$', '', string) # Delete empty queries
	string = re.sub(r'&', '&amp;', string)
	return string

def parse_logline(line):
	""" This works with the following rsyslog format template 
	template(name="Connections" type="list") {
        property(name="timestamp" dateFormat="unixtimestamp")
        constant(value=" ")
        property(name="fromhost-ip")
        constant(value=" ")
        property(name="msg" droplastlf="on" )
        constant(value="\n")
        }

	and used as:
	if $programname == 'meneame_accesslog' then /ssd/meneame_access.log;Connections
	& ~
	"""

	fields = line.split()
	if len(fields) >= 7:
		log = dict()
		try:
			log['ts'] = int(fields[0])
			log['server_ip'] = fields[1]
			log['ip'] = fields[2]
			log['user'] = fields[3]

			if fields[3] == 'B':
				log['_blocked'] = True
			else: log['_blocked'] = False

			log['time'] = float(fields[4])
			log['server'] = fields[5]
			log['script'] = fields[6]
		except (ValueError, TypeError) as e:
			print >> sys.stderr, "Bad line in parse_logline", e, line
			return None
		return log
	else:
		return None

def add_log2dict(log, d):
	for k in [x for x in log if x != 'time' and x != 'ts' and x[0] != "_"]:
		if k not in d:
			d[k] = {}
		if log[k] not in d[k]:
			d[k][log[k]] = 1
		else:
			d[k][log[k]] += 1

def time_position_log(logfile, minutes):
	now = datetime.datetime.now()
	goal = now - datetime.timedelta(minutes=minutes)

	base = 0
	logfile.seek(0, 2)
	top = logfile.tell()
	while top - base > 1000:
		pos = base + (top - base) / 2
		logfile.seek(pos, 0)
		logfile.readline() #Clean first line
		line = logfile.readline()
		if not line: 
			top = pos
			continue
		log = parse_logline(line)
		try:
			log_date = datetime.datetime.fromtimestamp(log['ts'])
		except (ValueError, TypeError) as e:
			print >> sys.stderr, "Bad line in time_position_log:", e, line
			base = pos
			continue

		if log_date < goal:
			base = pos
		else:
			top = pos
	return
		


class DBM(object):
	""" Helper class to hold select and update connections """

	connections = {"select": None, "update": None}

	@classmethod
	def cursor(cls, c_type="select"):
		if not cls.connections[c_type]:
			cls.connections[c_type] = MySQLdb.connect(host = dbconf.dbserver[c_type], user = dbconf.dbserver['user'], passwd = dbconf.dbserver['pass'], db = dbconf.dbserver['db'], charset = "utf8", use_unicode = True)
		return cls.connections[c_type].cursor()

	@classmethod
	def close(cls, c_type="select"):
		if cls.connections[c_type]:
			try:
				cls.connections[c_type].close()
			except: pass
			cls.connections[c_type] = None

	@classmethod
	def commit(cls, c_type="update"):
		if cls.connections[c_type]:
			cls.connections[c_type].commit()


class BaseBlogs(object):

	def __init__(self):
		self.links = set()

	def read_feed(self):
		entries = 0

		""" Get last rss read """
		d = DBM.cursor()
		d.execute("select unix_timestamp(max(date)) from rss where blog_id = %s limit 1", (self.id,))
		self.last_read, = d.fetchone()
		d.close()

		c = DBM.cursor('update')
		c.execute("update blogs set blog_feed_read = now() where blog_id = %s", (self.id,))
		DBM.commit()
		now = time.time()

		try:
			if self.last_read:
				modified = time.gmtime(self.last_read)
			else:
				modified = time.gmtime(now - dbconf.blogs['min_hours']*3600)
			#print " --- DEBUG: ", modified, self.feed
			doc = feedparser.parse(self.feed, modified=modified)
		except (urllib2.URLError, urllib2.HTTPError, UnicodeEncodeError), e:
			print " --- ERROR: connection failed (%s) %s" % (e, self.feed)
			DBM.commit()
			c.close()
			return False

		if not doc.entries or doc.status == 304:
			print "   * Not modified"
			DBM.commit()
			c.close()
			return entries

		#print "---------------------------------------------------------------------------------------------------------------------------------------------"
		#print "BLOG"
		#for e in doc.entries:
		#	print "###########################################################################"
		#	print e
		#	print "###########################################################################"
		#print "---------------------------------------------------------------------------------------------------------------------------------------------"

		for i, e in enumerate(doc.entries):
			if i >= dbconf.blogs['max_feeds']:
				break

			if hasattr(e, 'published_parsed') and e.published_parsed:
				timestamp = time.mktime(e.published_parsed)
			elif hasattr(e, 'updated_parsed') and e.updated_parsed:
				timestamp = time.mktime(e.updated_parsed)
			else:
				continue

			if timestamp > now:
				timestamp = now

			try:
				if timestamp < time.time() - dbconf.blogs['min_hours']*3600 or (self.read and timestamp <  self.read):
					#print "Old entry:", e.link, e.updated, e.updated_parsed, time.time() - timestamp
					pass
				else:
					try:
						if hasattr(e, 'meneame_url'):
							link_clean = clean_url(e.meneame_url)
						else:
							link_clean = clean_url(e.link)
						image = ""
						if hasattr(e, 'content') and e.content:
							tree = BeautifulSoup(e.content[0]['value'])
							img = tree.find('img')
							if img:
								i = img.find('src')
								if i: 
									image = img.get('src')[:250]

								i = img.find('data-original')
								if i:
									image = img.get('data-original')[:250]

						if not image:
							if hasattr(e, 'media_content') and e.media_content:
								if hasattr(e.media_content[0], 'type'):
									if "image" in e.media_content[0]['type']:
										image = clean_url(e.media_content[0]['url'][:250])

							if hasattr(e, 'enclosures') and e.enclosures:
								if hasattr(e.enclosures[0], 'type'):
									if "image" in e.enclosures[0]['type']:
										image = clean_url(e.enclosures[0]['href'][:250])

						title = e.title[:250]
						summary = e.summary[:550]
						c.execute("insert into rss (blog_id, user_id, date, date_parsed, title, summary, url, media_url) values (%s, %s, FROM_UNIXTIME(%s), FROM_UNIXTIME(%s), %s, %s, %s, %s)", (self.id, self.user_id, now, timestamp, title, summary, link_clean, image))
					except _mysql_exceptions.IntegrityError, e:
						""" Duplicated url, ignore it"""
						print "   - insert failed (%s)" % (e,)
						pass
					else:
						print " +++ Added: ", e.link
						self.links.add(e.link)
						entries += 1
			except AttributeError, e:
					print "   - not existing attribute (%s)" % (e,)
					pass

		DBM.commit()
		c.close()
		return entries


	def get_feed_info(self):
		""" Get feed url by analysing the HTML """
		print "Reading blog info: ", self.url
		feed = None
		title = None
		try:
			print "Getting " + self.url
			req = urllib2.Request(url=self.url, headers=hdr_string)
			doc = urllib2.urlopen(req, timeout=20).read()
			soup = BeautifulSoup(doc, parseOnlyThese=SoupStrainer('head'))
			if not soup.head:
				""" Buggy blogs without <head> :( """
				print "   >> Parsing all"
				soup = BeautifulSoup(doc)

			if soup.title and soup.title.string:
				title = soup.title.string.strip()
		except (socket.error, socket.timeout, urllib2.URLError, urllib2.HTTPError, UnicodeEncodeError, httplib.BadStatusLine, TypeError), e:
			print "+++ ERROR: "
			print e
			pass
		else:
			""" Search for feed urls """
			link1 = soup.find('link', type='application/rss+xml')
			link2 = soup.find('link', rel="alternate")
			if link1 and link1['href']:
				print " > Link 1: " + link1['href']
				feed = link1['href']
			elif link2 and link2['href']:
				print " > Link 2: " + link2['href']
				feed = link2['href']
			else:
				print " > Link 3, searching..."
				all_res = re_link.findall(unicode(soup))
				t_url = None
				for line in all_res:
					g = re_href.search(line)
					if g and g.group(1).find('comment') < 0:
						t_url = g.group(1)
						feed = t_url
						print " > Link 3: " + feed

		if feed:
			feed = self.fixFeedURL(feed)

			if self.feed != feed:
				self.title = title
				self.feed = feed;
				self.save_feed_info()


	def fixFeedURL(self, feed):
		feed = feed.replace("feed:", "")
		if feed[0:5] != "http:" and feed[0:6] != "https:":
			canonical_url  = self.url.replace('http://', '').replace('https://', '').strip("/")
			canonical_feed = feed.strip("/")

			if canonical_feed.startswith(canonical_url):
				feed = self.url.strip("/") + '/' + canonical_feed.replace(canonical_url, "").strip("/")
			elif feed.startswith("//"):
				if self.url.startswith("http:"):
					feed = "http:" + feed
				else:
					feed = "https:" + feed
			else:
				feed = self.url.strip("/") + '/' + feed.strip("/")

			print " > Fixing feed URL: " + feed

		return feed


	def save_feed_info(self):
		""" Save feed_url, title and last checked time in blogs table """

		if self.title:
			self.title = self.title[0:125]
		else:
			self.title = ""

		c = DBM.cursor('update')
		print "Updating to blog:"
		print "                  Title: %s" % self.title
		print "                  URL: %s" % self.base_url
		print "                  Feed: %s" % self.feed
		c.execute("update blogs set blog_feed = %s, blog_title = %s, blog_feed_checked = now() where blog_id = %s", (self.feed, self.title, self.id))
		DBM.commit()
		c.close()


	def is_banned(self):
		local_domain = dbconf.domain.replace('http://', '').replace('https://', '').replace('www.', '')
		hostname = re.sub('^www\.', '', re.sub(':[0-9]+$', '', urlparse(self.url)[1]))
		if re.search(re.escape(local_domain)+r'$', hostname):
			print " > Url is the same as local domain: ", local_domain, hostname
			return True


		c = DBM.cursor()
		c.execute("select count(*) from bans where ban_text in (%s, %s, %s, %s) AND ban_type in ('hostname','punished_hostname') AND (ban_expire IS null OR ban_expire > now())", (self.base_url, 'www.'+self.base_url, hostname, 'www.'+hostname));
		# print("select count(*) from bans where ban_text in (%s, %s, %s, %s) AND ban_type in ('hostname','punished_hostname') AND (ban_expire IS null OR ban_expire > now())" % (self.base_url, 'www.'+self.base_url, hostname, 'www.'+hostname));
		r = c.fetchone()
		c.close()
		if r[0] > 0:
			print " > Banned ", hostname
			return True
		else:
			return False

