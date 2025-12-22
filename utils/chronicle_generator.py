def generate_chronicle(logs, players):
    html = """
    <h1 style="text-align:center;color:#ff5722;">🌀 Abszurdvár Krónikája 🌀</h1>
    <h2>A 2025-ös Polgármesteri Kampány Hivatalos Történelme</h2>
    <p><strong>Polgármesterjelöltek:</strong> """ + ", ".join(players) + """</p>
    <hr>
    <h3>Események időrendben:</h3>
    <ol>
    """
    for log in logs[-50:]:  # utolsó 50 sor
        html += f"<li>{log}</li>"
    html += """
    </ol>
    <p style="text-align:center;font-size:20px;margin-top:50px;">
    Köszönjük a részvételt! Abszurdvár örökké élni fog a szívetekben! 🐐🍌😂
    </p>
    """
    return html