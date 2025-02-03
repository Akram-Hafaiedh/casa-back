<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Your Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #FF2D20;
        }
        .button {
            background-color: #FF2D20;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to Casa Group, {{ $user->first_name }} {{ $user->last_name }}!</h1>
        <p>We're thrilled to have you join our staff. As part of your onboarding, please set your password by clicking the button below:</p>
        <a href="{{ $link }}" class="button">Set Your Password</a>
        <p>If you run into any issues or have questions, our support team is here to help you every step of the way.</p>
        <p>Looking forward to a great journey ahead!</p>
        <p>Best Regards,<br>The Casa Group Team</p>
    </div>
</body>
</html>
