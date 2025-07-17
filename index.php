<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Product Manager Agent Chat</title>
  <style>
    body { font-family: sans-serif; max-width: 600px; margin: 2rem auto; }
    #chat { border: 1px solid #ccc; padding: 1rem; min-height: 200px; }
    .msg { margin-bottom: 1rem; }
    .user { font-weight: bold; }
    .agent { color: darkblue; }
  </style>
</head>
<body>
  <h2>Product Manager Agent</h2>
  <div id="chat"></div>

  <form id="chatForm">
    <input type="text" id="message" placeholder="Type your message..." style="width: 80%;" />
    <button type="submit">Send</button>
  </form>

  <script>
    const chatForm = document.getElementById('chatForm');
    const chatBox = document.getElementById('chat');

    chatForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const input = document.getElementById('message');
      const msg = input.value.trim();
      if (!msg) return;

      appendMessage('You', msg);
      input.value = '';

      const response = await fetch('chat_pm.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'message=' + encodeURIComponent(msg)
      });

      const data = await response.json();
      appendMessage('Agent', data.response);
    });

    function appendMessage(sender, text) {
      const div = document.createElement('div');
      div.classList.add('msg');
      div.innerHTML = `<span class="${sender === 'You' ? 'user' : 'agent'}">${sender}:</span> ${text}`;
      chatBox.appendChild(div);
      chatBox.scrollTop = chatBox.scrollHeight;
    }

    // Initial welcome message
    appendMessage('Agent', 'Hi! What would you like to develop?');
  </script>
</body>
</html>
