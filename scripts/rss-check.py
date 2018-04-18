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

	print "------------------------------ BEGIN RSS CHECK -", time.strftime("%c"), " UTC ------------------------------"

	"""
	Get the possible blog we can read
	"""
	now = time.time()
	blogs = set()
	results = set()
	cursor = DBM.cursor()

	#query = """
	#	SELECT blog_id, blog_url, blog_feed,
	#			UNIX_TIMESTAMP(blog_feed_checked),
	#			UNIX_TIMESTAMP(blog_feed_read)
	#		FROM sub_statuses, links, blogs
	#		WHERE 
	#			(id = 1 AND status = "published" AND date > date_sub(now(), interval %s day)
	#			 AND link_id = link
	#			 AND blog_id = link_blog
	#			 AND blog_type not in ('disabled', 'aggregator')
	#			 AND (blog_feed_checked is null OR blog_feed_checked < date_sub(now(), interval %s day)))
	#	UNION
	#	SELECT blog_id, blog_url, blog_feed,
        #                       UNIX_TIMESTAMP(blog_feed_checked),
        #                        UNIX_TIMESTAMP(blog_feed_read)
	#		FROM blogs
	#		WHERE blog_type = 'aggregator'
	#	GROUP BY blog_id
	#"""

	query = """
		SELECT blog_id, blog_url, blog_feed,
				UNIX_TIMESTAMP(blog_feed_checked),
				UNIX_TIMESTAMP(blog_feed_read)
			FROM sub_statuses, links, blogs
			WHERE 
				(id = 1 AND status = "published" AND date > date_sub(now(), interval %s day)
				 AND link_id = link
				 AND blog_id = link_blog
				 AND blog_type not in ('disabled', 'aggregator')
				 AND (blog_feed_checked is null OR blog_feed_checked < date_sub(now(), interval %s day)))
		GROUP BY blog_id
	"""

	cursor.execute(query, (dbconf.blogs['days_blogs'], dbconf.blogs['days_blogs_checked']))
	for row in cursor:
		blog = BaseBlogs()
		blog.id, blog.url, blog.feed, blog.checked, blog.read = row
		blog.user_id = 0
		blog.base_url = blog.url.replace('http://', '').replace('https://', '').replace('www.', '')
		if blog.is_banned():
			continue
		blogs.add(blog)

	cursor.close()

	print("Checking blogs: (%s)" % len(blogs))

	feeds_read = 0
	# Sort the set of blogs by date of read
	sorted_blogs = sorted(blogs, key=lambda x: x.read)
	for blog in sorted_blogs:
		if not blog.is_banned():
			blog.get_feed_info()

			if blog.feed:
				print " > Added ", blog.id, blog.url, blog.feed
				feeds_read += 1

	print "------------------------------ END - Blogs added: ", feeds_read, " - ", time.strftime("%c"), " UTC ------------------------------"


if __name__ == "__main__":
	main()

