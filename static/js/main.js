function addMessage(msg) {
    const div = document.createElement('div');
    div.textContent = msg;
    document.getElementById('messages').appendChild(div);
    div.scrollIntoView();
}

socket.on('message', data => addMessage(data.msg));

socket.on('update_players', players => {
    document.getElementById('player_list').innerHTML = players.join(', ');
});

socket.on('new_problem', data => {
    hideAllPhases();
    document.getElementById('phase1').classList.remove('hidden');
    document.getElementById('problem').innerText = data.problem;
    document.getElementById('twist').innerText = data.twist;

    // Kecske timer
    setTimeout(() => playSound('/static/sounds/goat_bleat.mp3'), 30000);
});

socket.on('new_image', data => {
    hideAllPhases();
    document.getElementById('phase2').classList.remove('hidden');
    const canvas = document.getElementById('collage_canvas');
    const ctx = canvas.getContext('2d');
    const img = new Image();
    img.onload = () => ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    img.src = data.image_url;
});

socket.on('new_caption', data => {
    const canvas = document.getElementById('collage_canvas');
    const ctx = canvas.getContext('2d');
    ctx.font = '30px Comic Sans MS';
    ctx.fillStyle = 'white';
    ctx.strokeStyle = 'black';
    ctx.lineWidth = 4;
    ctx.strokeText(data.caption, 20, canvas.height - 40);
    ctx.fillText(data.caption, 20, canvas.height - 40);
});

socket.on('new_scandal', data => {
    hideAllPhases();
    document.getElementById('phase3').classList.remove('hidden');
    document.getElementById('scandal').innerText = data.scandal;
    playSound('/static/sounds/drum_roll.mp3');
});

socket.on('add_chaos', data => {
    const img = new Image();
    img.src = data.element;
    img.style.position = 'fixed';
    img.style.width = '150px';
    img.style.pointerEvents = 'none';
    img.style.left = Math.random() * (window.innerWidth - 150) + 'px';
    img.style.top = '-200px';
    img.style.transition = 'top 5s linear';
    document.body.appendChild(img);
    setTimeout(() => img.style.top = '100vh', 100);
    setTimeout(() => img.remove(), 6000);
});

socket.on('game_over', data => {
    document.getElementById('game_area').classList.add('hidden');
    const chronicle = document.getElementById('chronicle');
    chronicle.classList.remove('hidden');
    chronicle.innerHTML = data.chronicle;
});

function hideAllPhases() {
    document.querySelectorAll('.phase').forEach(p => p.classList.add('hidden'));
}

function sendChat() {
    const input = document.getElementById('chat_input');
    const msg = input.value.trim();
    if (msg) {
        socket.emit('chat', {room, username, msg});
        input.value = '';
    }
}

function addCaption() {
    const input = document.getElementById('caption_input');
    const caption = input.value.trim();
    if (caption) {
        socket.emit('add_caption', {room, username, caption});
        input.value = '';
    }
}

function pressChaos() {
    socket.emit('chaos_button', {room});
}

function startPhase(phase) {
    if (confirm(`Biztosan indítod a ${phase}. fázist?`)) {
        socket.emit('start_phase', {room, phase});
    }
}

function endGame() {
    if (confirm("Vége a kampánynak? Generáljuk az Abszurdvár Krónikát?")) {
        socket.emit('end_game', {room});
    }
}

function playSound(url) {
    new Audio(url).play();
}