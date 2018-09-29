# -*- coding: utf-8 -*-
"""
Created on Sat Sep 29 09:30:01 2018

@author: Fengjinyuan
"""

#依赖 beautifulsoup4

import os
import http.cookiejar as hc
import urllib.request as ur
import re #正则表达式
import bs4
from bs4 import BeautifulSoup as BS
#%%
#url = "http://www.baidu.com"

##Method 1
#response1 = ur.urlopen(url)
#print(response1.getcode())
#print(len(response1.read()))
##Method 2
#req = ur.Request(url)
##模拟浏览器
#req.add_header("user-agent","Mozilla/5.0")
#response2 = ur.urlopen(url)
#print(response2.getcode())
#print(len(response2.read()))
#
##Method 3
#cookie = hc.CookieJar()
##urllib 加入cookie能力
#opener = ur.build_opener(ur.HTTPCookieProcessor(cookie))
#ur.install_opener(opener)
#response3=ur.urlopen(url)
#print(response1.getcode())
#print(len(response1.read()))
#print(cookie)

#html_doc = """
#<html><head><title>The Dormouse's story</title></head>
#<body>
#<p class="title"><b>The Dormouse's story</b></p>
#<p class="story">Once upon a time there were three little sisters; and their names were
#<a href="http://example.com/elsie" class="sister" id="link1">Elsie</a>,
#<a href="http://example.com/lacie" class="sister" id="link2">Lacie</a> and
#<a href="http://example.com/tillie" class="sister" id="link3">Tillie</a>;
#and they lived at the bottom of a well.</p>
#<p class="story">...</p>
#"""
##创建一个BeautifulSoup解析对象
#soup = BS(html_doc,"html.parser",from_encoding="utf-8")
##获取所有的链接
#links = soup.find_all('a')
#print("所有的链接")
#for link in links:
#    print(link.name)
#    print(link['href'])
#    print(link.get_text())
# 
#print("获取特定的URL地址")
#link_node = soup.find('a',href="http://example.com/elsie")
#print(link_node.name)
#print(link_node['href'])
#print(link_node['class'])
#print(link_node.get_text())
# 
#print("正则表达式匹配")
#link_node = soup.find('a',href=re.compile(r"ti"))
#print(link_node.name)
#print(link_node['href'])
#print(link_node['class'])
#print(link_node.get_text())
# 
#print("获取P段落的文字")
#p_node = soup.find('p',class_='story')
#print(p_node.name)
#print(p_node['class'])
#print(p_node.get_text())

#%%
def Schedule(a,b,c):
    '''''
    a:已经下载的数据块
    b:数据块的大小
    c:远程文件的大小
    '''
    per = 100.0 * a * b / c
    if per > 100 :
        per = 100
    print('%.2f%%' % per)

def Capture(url):
    req = ur.Request(url)
    #模拟浏览器
    req.add_header("user-agent","Mozilla/5.0")
    response = ur.urlopen(url)
    print(response.getcode())
    resbyte = response.read()
    html_doc = resbyte.decode('utf-8')
    return html_doc
def GetDownloadPage(html_doc):
    soup = BS(html_doc,"html.parser")
    os_type = soup.find_all('a',class_="card-title")
    for i in range(len(os_type)):
        print(i,os_type[i].get_text())
    os_index = int(input("Please intput your OS type (index):"))

    os_list = soup.find_all('div',class_='card')
    caption_name=[]
    caption_sets=os_list[os_index].find_all('td', class_="bold pl-3")
    for i in range(len(caption_sets)):
        caption_name.append(caption_sets[i].get_text())
    #print(caption_name)

    download_url=[]
    download_name=[]
    download_url_sets=os_list[os_index].find_all('td', class_="w-50 pl-4")
    for i in range(len(download_url_sets)):
        download_url.append(download_url_sets[i].a['href'])
        download_name.append(download_url_sets[i].a.get_text())
    for i in range(len(caption_name)):
        print(i)
        print(caption_name[i])
        print(download_name[i])
        #print(download_url[i])
    return download_url
def GetDependency(durl):
    html_doc = Capture(durl)
    #print(html_doc)
    soup = BS(html_doc,"html.parser")
    pkg_link=soup.find_all('a',rel="nofollow")
    print(pkg_link)
    pattern=re.findall(r'<h2>Requires</h2>(.*?)\n<div id="requiredby" class="mb-3">',html_doc,re.S)  
    #re.S遇到\n不停止  r''表示不用转义
    soup2= BS(pattern[0],"html.parser")
    dep_link_list=soup2.find_all('a')
    dep_link_name=[]
    for i in range(len(dep_link_list)):
        dep_link_name.append(dep_link_list[i].get_text())
        print(dep_link_list[i].get_text())
    return dep_link_name
    
def DownloadPkg(durl,path,func):
    if (os.path.exists(path))==False :
        print("Dir not exist!")
        return
    html_doc = Capture(durl)
    #print(html_doc)
    soup = BS(html_doc,"html.parser")
    pkg_link=soup.find('a',rel="nofollow")
    print('即将开始下载文件',pkg_link.get_text())
    ur.urlretrieve(pkg_link['href'],path+pkg_link.get_text(),func)
    print(pkg_link.get_text(),'下载完成!')
def DonwloadDependency(dep_list,path,func):
    if (os.path.exists(path))==False :
        print("Dir not exist!")
        return
    for i in range(len(dep_list)):
        print("开始处理包名",dep_list[i])
        search_url = "https://pkgs.org/download/"+dep_list[i]
        html_doc = Capture(search_url)
        url_list=GetDownloadPage(html_doc)
        path='./downloadpkg/'
        pkg_index=int(input("Please intput your package number that will install (index):"))
        DownloadPkg(url_list[pkg_index],path,func)
        print("当前进度 %d \ %d" % (i,len(dep_list)))
    

if __name__ == '__main__':
    path='./downloadpkg/'
    package_name='gnome-panel'
    search_url = "https://pkgs.org/download/"+package_name
    html_doc = Capture(search_url)
    url_list=GetDownloadPage(html_doc)
    pkg_index=int(input("Please intput your package number that will install (index):"))
    DownloadPkg(url_list[pkg_index],path,Schedule)
    ans = int(input("Do you need to install dependency (0 is Yes 1 is No) Please input (0 or 1)"))
    if ans == 0:
       dep_list=GetDependency(url_list[pkg_index])
       DonwloadDependency(dep_list,path,Schedule)
    else:
      print("Work Finished!")
