<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Product Manager Agent Chat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', sans-serif;
    }

    .container {
      max-width: 700px;
      margin-top: 50px;
    }

    .chat-box {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      height: 450px;
      overflow-y: auto;
      margin-bottom: 20px;
    }

    .msg {
      margin-bottom: 1rem;
    }

    .user {
      font-weight: bold;
    }

    .agent {
      color: #0d6efd;
    }

    .typing {
      font-style: italic;
      color: gray;
      margin-bottom: 1rem;
    }
  </style>
</head>
<body>

<div class="container">
  <h3 class="text-center mb-4">Product Manager Agent</h3>
  <div id="chat" class="chat-box"></div>

  <form id="chatForm" class="input-group">
    <input type="text" id="message" class="form-control" placeholder="Type your message..." required autocomplete="off">
    <button type="submit" class="btn btn-primary">Send</button>
  </form>
</div>

<script>
  const chatForm = document.getElementById('chatForm');
  const chatBox = document.getElementById('chat');
  const input = document.getElementById('message');

  function appendMessage(sender, text) {
    const div = document.createElement('div');
    div.classList.add('msg');
    div.innerHTML = `<span class="${sender === 'You' ? 'user' : 'agent'}">${sender}:</span> ${text}`;
    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
  }

  function showTyping() {
    const typing = document.createElement('div');
    typing.id = 'typing';
    typing.classList.add('typing');
    typing.textContent = 'Agent is typing...';
    chatBox.appendChild(typing);
    chatBox.scrollTop = chatBox.scrollHeight;
  }

  function removeTyping() {
    const typing = document.getElementById('typing');
    if (typing) typing.remove();
  }

  chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = input.value.trim();
    if (!msg) return;

    appendMessage('You', msg);
    input.value = '';
    input.focus();

    showTyping();

    const response = await fetch('chat_pm.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'message=' + encodeURIComponent(msg)
    });

    const data = await response.json();
    removeTyping();
    appendMessage('Agent', data.response);
  });

  // Initial welcome message
  appendMessage('Agent', 'Hi! What would you like to develop?');
</script>

</body>
</html>
