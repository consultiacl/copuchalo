#! /usr/bin/env python
# -*- coding: utf-8 -*-

import time
import gettext
_ = gettext.gettext
import dbconf
from utils import DBM, BaseBlogs
import urllib
import urllib2
import socket

import sys
import codecs
import locale

if sys.stdout.isatty():
	default_encoding = sys.stdout.encoding
else:
	default_encoding = locale.getpreferredencoding()



def main():
	"""
	Main loop of the process
	"""
	# timeout in seconds
	timeout = 10
	socket.setdefaulttimeout(timeout)

	print "------------------------------ BEGIN FEEDS UPDATE -", time.strftime("%c"), "UTC ------------------------------"

	# Delete old entries
	update_cursor = DBM.cursor('update')
	query = """
		DELETE FROM rss
			WHERE date_parsed < date_sub(now(), interval %s day)
	"""
	print "Deleting old entries"
	update_cursor.execute(query, (dbconf.blogs['days_to_keep'],))
	DBM.commit()
	update_cursor.close()

	"""
	Get the possible blog we can read
	"""
	now = time.time()
	cursor = DBM.cursor()

	query = """
		SELECT blog_id, blog_url, blog_feed,
				UNIX_TIMESTAMP(blog_feed_checked),
				UNIX_TIMESTAMP(blog_feed_read)
			FROM sub_statuses, links, blogs
			WHERE 
				(id = 1
				AND status = "published" AND date > date_sub(now(), interval %s day)
				AND link_id = link
				AND blog_id = link_blog
				AND blog_feed_checked is not null
				AND blog_type <> 'disabled'
				AND blog_feed is not null)
		UNION
		SELECT blog_id, blog_url, blog_feed,
                                UNIX_TIMESTAMP(blog_feed_checked),
                                UNIX_TIMESTAMP(blog_feed_read)
			FROM blogs
			WHERE blog_type = 'aggregator'
		GROUP BY blog_id
	"""
	feeds_read = 0
	print "Reading feeds..."
	cursor.execute(query, (dbconf.blogs['days_blogs'],))
	for row in cursor:
		blog = BaseBlogs()
		blog.id, blog.url, blog.feed, blog.checked, blog.read = row
		blog.user_id = 0
		blog.base_url = blog.url.replace('http://', '').replace('https://', '').replace('www.', '')
		if blog.is_banned():
			continue
		print " >>> Reading: %s (%s)" % (blog.url, blog.feed)
		entries = blog.read_feed()
		print "     Blog ", blog.id, " has ", entries, " entries %s" % blog.url
		feeds_read += 1

	cursor.close()

	print "------------------------------ END - ", feeds_read, " feeds read - ", time.strftime("%c"), "UTC ------------------------------"

if __name__ == "__main__":
	main()

