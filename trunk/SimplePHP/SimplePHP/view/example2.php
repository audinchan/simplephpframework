<html>
<head>
<title>example2</title>
</head>
<body>
<if exp="!empty($username)"/>
Hello，${username}。
<else/>
Hello，匿名用户。
<end/>
<viewlet name="ViewletExample" params="username=${username};otherparam=xxx" />
</body>
</html>