<!DOCTYPE html>
<html>
<head>
    <title>New Contact Message</title>
</head>
<body>
    <h1>New Contact Message Received</h1>
    <p><strong>Name:</strong> {{ $name }}</p>
    <p><strong>Email:</strong> {{ $email }}</p>
    <p><strong>Phone:</strong> {{ $phone }}</p>
    <p><strong>Message:</strong></p>
    <p>{{ $messageContent }}</p>
</body>
</html>