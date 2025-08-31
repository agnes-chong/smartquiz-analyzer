let timeLeft = {{ $quiz->duration_minutes * 60 }};
setInterval(() => {
    document.getElementById('timer').innerHTML = `Time Left: ${timeLeft}s`;
    if (timeLeft-- <= 0) {
        document.forms[0].submit();
    }
}, 1000);