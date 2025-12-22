<<<<<<< HEAD
from flask import Flask, render_template, request, jsonify
from flask_socketio import SocketIO, emit, join_room
=======
from flask import Flask, render_template, request, jsonify, redirect, url_for
from flask_socketio import SocketIO, emit, join_room, leave_room
>>>>>>> 07e333b53e5e2d615cac804340430a909b370842
import json
import random
from utils.chronicle_generator import generate_chronicle

app = Flask(__name__)
app.config['SECRET_KEY'] = 'abszurdvar2025!'
socketio = SocketIO(app, cors_allowed_origins="*")

<<<<<<< HEAD
# Szobák memóriában
rooms = {}
=======
# Szobák tárolása memóriában
rooms = {}  # {'1234': {'players': [...], 'phase': 1, 'data': {}, 'logs': [], 'password': ''}}
>>>>>>> 07e333b53e5e2d615cac804340430a909b370842

# Adatok betöltése
with open('data/problems.json', encoding='utf-8') as f:
    problems = json.load(f)
with open('data/twists.json', encoding='utf-8') as f:
    twists = json.load(f)

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/create_room', methods=['POST'])
def create_room():
    password = request.form.get('password', '')
    room_id = str(random.randint(1000, 9999))
    while room_id in rooms:
        room_id = str(random.randint(1000, 9999))
    rooms[room_id] = {
        'players': [],
        'phase': 0,
        'data': {},
        'logs': [f"Szoba létrehozva (ID: {room_id})"],
        'password': password
    }
    return jsonify({'room_id': room_id})

@app.route('/room/<room_id>')
def room(room_id):
    if room_id not in rooms:
        return "Nincs ilyen szoba!", 404
    return render_template('room.html', room_id=room_id)

@socketio.on('join')
def on_join(data):
    room = data['room']
    username = data['username']
    password = data.get('password', '')
<<<<<<< HEAD
    if room not in rooms or rooms[room]['password'] != password:
        emit('error', {'msg': 'Hibás szoba vagy jelszó!'})
        return
=======

    if room not in rooms or rooms[room]['password'] != password:
        emit('error', {'msg': 'Hibás szoba vagy jelszó!'})
        return

>>>>>>> 07e333b53e5e2d615cac804340430a909b370842
    join_room(room)
    if username not in rooms[room]['players']:
        rooms[room]['players'].append(username)
        rooms[room]['logs'].append(f"{username} belépett a városba!")
<<<<<<< HEAD
=======

>>>>>>> 07e333b53e5e2d615cac804340430a909b370842
    emit('joined', {'players': rooms[room]['players'], 'logs': rooms[room]['logs'][-20:]})
    emit('message', {'msg': f"🎉 {username} megérkezett Abszurdvárba!"}, room=room)
    emit('update_players', rooms[room]['players'], room=room)

@socketio.on('chat')
def handle_chat(data):
    room = data['room']
    username = data['username']
    msg = data['msg']
    full_msg = f"{username}: {msg}"
    rooms[room]['logs'].append(full_msg)
    emit('message', {'msg': full_msg}, room=room)

@socketio.on('start_phase')
def start_phase(data):
    room = data['room']
    phase = data['phase']
    rooms[room]['phase'] = phase
<<<<<<< HEAD
    rooms[room]['data'] = {}
=======
    rooms[room]['data'] = {}  # Reset phase data

>>>>>>> 07e333b53e5e2d615cac804340430a909b370842
    if phase == 1:
        problem = random.choice(problems['phase1'])
        twist = random.choice(twists['generic'])
        emit('new_problem', {'problem': problem, 'twist': twist}, room=room)
        rooms[room]['logs'].append("🗳️ 1. Fázis indult: Választási Ígéretek!")
<<<<<<< HEAD
    elif phase == 2:
        emit('new_image', {'image_url': '/static/images/banana_mayor.png'}, room=room)
        rooms[room]['logs'].append("🎨 2. Fázis indult: Közösségi Képfaragó!")
=======

    elif phase == 2:
        emit('new_image', {'image_url': '/static/images/banana_mayor.png'}, room=room)
        rooms[room]['logs'].append("🎨 2. Fázis indult: Közösségi Képfaragó!")

>>>>>>> 07e333b53e5e2d615cac804340430a909b370842
    elif phase == 3:
        scandal = random.choice(problems['phase3'])
        target = random.choice(rooms[room]['players'])
        formatted_scandal = scandal.replace("X. játékos", target)
        emit('new_scandal', {'scandal': formatted_scandal, 'target': target}, room=room)
        rooms[room]['logs'].append("📰 3. Fázis indult: Botrány-Híradó!")

@socketio.on('add_caption')
def add_caption(data):
    room = data['room']
    username = data['username']
    caption = data['caption']
    full = f"{username}: {caption}"
    rooms[room]['data'].setdefault('captions', []).append(full)
    emit('new_caption', {'caption': full}, room=room)

@socketio.on('chaos_button')
def chaos_button(data):
    room = data['room']
    emit('add_chaos', {'element': '/static/images/flying_goat.gif'}, room=room)
    emit('message', {'msg': '🐐 KÁOSZ GOMB MEGNYOMVA! Repülő kecskék támadnak!'}, room=room)
    rooms[room]['logs'].append("Káosz gomb megnyomva!")

@socketio.on('end_game')
def end_game(data):
    room = data['room']
    chronicle_html = generate_chronicle(rooms[room]['logs'], rooms[room]['players'])
    emit('game_over', {'chronicle': chronicle_html}, room=room)

if __name__ == '__main__':
<<<<<<< HEAD
    socketio.run(app, host='0.0.0.0', port=5000, debug=False)
=======
    socketio.run(app, debug=True, port=5000)
>>>>>>> 07e333b53e5e2d615cac804340430a909b370842
