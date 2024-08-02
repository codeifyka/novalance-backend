<template src="./chat_room.html"></template>

<script src="./ChatRoom.component.js" />

<style>
.chat-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background-color: #f4f4f4;
}
.chat-box {
  width: 80%;
  max-width: 600px;
  background-color: white;
  padding: 20px;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
  border-radius: 10px;
}
.messages {
  max-height: 300px;
  overflow-y: auto;
  margin-bottom: 20px;
}
.message {
  margin: 5px 0;
}
.message.self {
  text-align: right;
}
</style>