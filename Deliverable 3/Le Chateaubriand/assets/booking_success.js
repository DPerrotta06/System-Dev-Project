//NOTIFS FOR GOING BACK TO LANDING PAGE AFTER BOOKING
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('booked') === '1') {
        const cleanUrl = window.location.pathname;
        window.history.replaceState({}, document.title, cleanUrl);
        if ("Notification" in window) {
            Notification.requestPermission().then(permission => {
                if (permission === "granted") {
                    new Notification("Booking Confirmed!", {
                        body: "Your event has been successfully booked. We hope to see you soon!",
                    });
                }
            });
        }
    }