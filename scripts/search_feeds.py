#! /usr/bin/env python
# -*- coding: utf-8 -*-

import sys
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
	# timeout in seconds
	timeout = 10
	socket.setdefaulttimeout(timeout)

	now = time.time()
	blog = BaseBlogs()
	blog.feed = sys.argv[1]
	print " >>> Reading: %s" % blog.feed
	entries = blog.search_feed()
	print "     Entries: ", entries

if __name__ == "__main__":
	main()

