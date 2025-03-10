window.onload = () => {
    const formatáltEgyenleg = new Intl.NumberFormat('en-US', { useGrouping: true }).format(egyenleg);

    document.getElementById('perselyegyenlegText').textContent = formatáltEgyenleg;

    if (userName) {
        document.getElementById('felhasznaloNev').textContent = userName;
        document.getElementById("bejelentkezesopcio").style.display = "none";
        document.getElementById("profilopcio").style.display = "block";
        document.getElementById("beallitasopcio").style.display = "block";
        document.getElementById("kijelentkezesopcio").style.display = "block";
        document.getElementById("perselyegyenleg").style.visibility = "visible";
        document.getElementById("szerepkor").style.visibility = "visible";
        document.getElementById("statisztika").style.visibility = "visible";
    } else {
        document.getElementById("profilopcio").style.display = "none";
        document.getElementById("beallitasopcio").style.display = "none";
        document.getElementById("kijelentkezesopcio").style.display = "none";
        document.getElementById("perselyegyenleg").style.visibility = "none";
        document.getElementById("szerepkor").style.visibility = "none";
        document.getElementById("statisztika").style.visibility = "none";
        document.getElementById("nemvagybejelentkezve").style.visibility = "visible";
        document.getElementById("statisztika").innerHTML = "";
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const monthYear = document.getElementById('month-year');
    const daysContainer = document.getElementById('days');
    const prevButton = document.getElementById('prev');
    const nextButton = document.getElementById('next');
    const months = ['Január', 'Február','Március', 'Április', 'Május',
        'Június', 'Július', 'Augusztus', 'Szept.',
        'Október','November','December'
    ];
    let currentDate = new Date();
    let today = new Date();
    function renderCalendar(date) {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const lastDay = new Date(year, month + 1, 0).getDate();
    monthYear.textContent = `${months[month]} ${year}`;
    daysContainer.innerHTML = '';
    
    // Current month's dates
    for (let i = 1; i <= lastDay; i++) {
    const dayDiv = document.createElement('div');
    dayDiv.textContent = i;
    if (i === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
    dayDiv.classList.add('today');
    }
    daysContainer.appendChild(dayDiv);
    }
    // Next month's dates
    const nextMonthStartDay = 7 - new Date(year, month + 1, 0).getDay() - 1;
    for (let i = 1; i <= nextMonthStartDay; i++) {
    const dayDiv = document.createElement('div');
    dayDiv.textContent = i;
    dayDiv.classList.add('fade');
    daysContainer.appendChild(dayDiv);
    }
    }
    prevButton.addEventListener('click', function () {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar(currentDate);
    });
    nextButton.addEventListener('click', function () {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar(currentDate);
    });
    renderCalendar(currentDate);
    });

    document.addEventListener('DOMContentLoaded',function() {
        const monthYear = document.getElementById('month-year');
    
        const months = ['Január', 'Február','Március', 'Április', 'Május',
            'Június', 'Július', 'Augusztus', 'Szept.',
            'Október','November','December'
        ];
    
        let currentDate = new Date();
        let today = new Date();
        
        function renderCalendar(date){
            const year = date.getFullYear();
            const month = date.getMonth();
            const firstDay = new Date(year, month, 1).getDay();
            const lastDay = new Date(year,month + 1, 0).getDate();
    
            monthYear.textContent = `${months[month]} ${year}`;
        }
    
        renderCalendar(currentDate);
    });
    
    document.addEventListener('DOMContentLoaded', function () {
        const monthYear = document.getElementById('month-year');
        const daysContainer = document.getElementById('days');
        const prevButton = document.getElementById('prev');
        const nextButton = document.getElementById('next');
        const months = ['Január', 'Február','Március', 'Április', 'Május',
            'Június', 'Július', 'Augusztus', 'Szept.',
            'Október','November','December'
        ];
        let currentDate = new Date();
        let today = new Date();
        function renderCalendar(date) {
        const year = date.getFullYear();
        const month = date.getMonth();
        const firstDay = new Date(year, month, 1).getDay();
        const lastDay = new Date(year, month + 1, 0).getDate();
        monthYear.textContent = `${months[month]} ${year}`;
        daysContainer.innerHTML = '';
        // Previous month's dates
        const prevMonthLastDay = new Date(year, month, 0).getDate();
        for (let i = firstDay; i > 0; i--) {
        const dayDiv = document.createElement('div');
        dayDiv.textContent = prevMonthLastDay - i + 1;
        dayDiv.classList.add('fade');
        daysContainer.appendChild(dayDiv);
        }
        // Current month's dates
        for (let i = 1; i <= lastDay; i++) {
        const dayDiv = document.createElement('div');
        dayDiv.textContent = i;
        if (i === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
        dayDiv.classList.add('today');
        }
        daysContainer.appendChild(dayDiv);
        }
        // Next month's dates
        const nextMonthStartDay = 7 - new Date(year, month + 1, 0).getDay() - 1;
        for (let i = 1; i <= nextMonthStartDay; i++) {
        const dayDiv = document.createElement('div');
        dayDiv.textContent = i;
        dayDiv.classList.add('fade');
        daysContainer.appendChild(dayDiv);
        }
        }
        prevButton.addEventListener('click', function () {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(currentDate);
        });
        nextButton.addEventListener('click', function () {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(currentDate);
        });
        renderCalendar(currentDate);
        });