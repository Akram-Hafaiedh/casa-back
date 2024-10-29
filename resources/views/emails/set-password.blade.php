<!DOCTYPE html>
<html>
<head>
    <title>Password Reset</title>
</head>
<body>
    <p>Hello {{ $user->name }},</p>
    <p>You have been added to our system. Please set your password using the link below:</p>
    <p><a href="{{ $link }}">Set Password</a></p>
</body>
</html>