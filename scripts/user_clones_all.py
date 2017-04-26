#! /usr/bin/env python
# -*- coding: utf-8 -*-

import dbconf
from utils import DBM
import argparse

def main():
	cursor = DBM.cursor()

	query = """select distinct clon.user_login, clon.user_login_register, users.user_login, users.user_login_register, clon.user_level, clon_ip, clon_date from users, users as clon, clones where clon_from = users.user_id and clon_to = clon.user_id and clon_date > date_sub(now(), interval 60 day)"""

	cursor.execute(query)
	print("%-16s (%-20s)\t%-16s (%-20s)\t%-20s\t%-12s\t%s" % ("clon", "clonreg", "user", "userreg", "ip", "level", "date"))
	print("---------------------------------------------------------------------------------------------------------------------------------------------");
	for clon, clonreg, user, userreg, level, ip, date in cursor:
		print("%-16s (%-20s)\t%-16s (%-20s)\t%-20s\t%-12s\t%s" % (clon, clonreg, user, userreg, ip, level, date))

if __name__ == "__main__":
	main()



