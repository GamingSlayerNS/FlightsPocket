document.addEventListener("DOMContentLoaded", () => {
    const stayForm = document.getElementById("stay-form");
    const resultsDiv = document.getElementById("stay-results");

    const cityInput = document.getElementById("city");
    if (cityInput) {
        cityInput.addEventListener("input", (e) => {
            resetCitiesPopup(cityInput);
        });
        cityInput.addEventListener("blur", (e) => {
            resetCitiesPopup(cityInput);
        });
        cityInput.addEventListener("focus", (e) => {
            resetCitiesPopup(cityInput);
        });
    }

    if (stayForm) {
        stayForm.addEventListener("submit", (e) => {
            e.preventDefault();
            resultsDiv.innerHTML = "";
            let errors = [];

            const city = cityInput.value.trim();
            const checkIn = new Date(document.getElementById("check-in").value + "T00:00:00");
            const checkOut = new Date(document.getElementById("check-out").value + "T00:00:00");
            const adults = parseInt(document.getElementById("adults-stay").value);
            const children = parseInt(document.getElementById("children-stay").value);
            const infants = parseInt(document.getElementById("infants-stay").value);
            const startDate = new Date("2024-09-01T00:00:00");
            const endDate = new Date("2024-12-01T23:59:59");
            const lowerCaseCities = validCities.map(function (item) {
                return item.toLowerCase();
            });

            if (!lowerCaseCities.includes(city.toLowerCase())) {
                errors.push("City must be a major city in Texas or California.");
            }
            if (checkIn < startDate || checkIn > endDate) {
                errors.push("Check-in date must be between Sep 1, 2024 and Dec 1, 2024.");
            }
            if (checkOut < startDate || checkOut > endDate) {
                errors.push("Check-out date must be between Sep 1, 2024 and Dec 1, 2024.");
            }
            if (checkOut <= checkIn) {
                errors.push("Check-out date must be after check-in date.");
            }

            const totalGuests = adults + children;
            const roomsNeeded = Math.ceil(totalGuests / 2);

            if (errors.length > 0) {
                resultsDiv.innerHTML = errors.join("<br>");
            } else {
                /* let output = `<h3>Stay Search Details</h3>
                              <p>City: ${city}</p>
                              <p>Check-in: ${checkIn.toDateString()}</p>
                              <p>Check-out: ${checkOut.toDateString()}</p>
                              <p>Adults: ${adults}</p>
                              <p>Children: ${children}</p>
                              <p>Infants: ${infants}</p>
                              <p>Rooms Needed: ${roomsNeeded}</p>`; */
                let output = `<div id="available-hotels"></div>`;
                resultsDiv.innerHTML = output;
                /* const cartButton = document.getElementById("cart");
                if (cartButton) {
                    cartButton.addEventListener("click", () => {
                        console.log("Hotel Button Clicked!");
                        const cart = {
                            hotel_id: id,
                            hotel_name: name,
                            hotel_city: hotelCity,
                            checkIn_date: checkInDate,
                            checkOut_date: checkOutDate,
                            passengers: { adults, children, infants },
                        };
                        try {
                            sessionStorage.setItem("hotels_cart", JSON.stringify(cart));
                        } catch { }
                        window.location.href = "cart.html";
                    });
                } */
            }

            const listEl = document.getElementById("available-hotels");
            if (listEl) {
                var xml = new XMLHttpRequest();
                xml.open('GET', 'db/hotels.xml', false);
                xml.send();
                var hotelData = xml.responseXML;
                if (hotelData) {
                    hotelData = (new DOMParser()).parseFromString(xml.responseText, 'text/xml');
                    var hotelList = hotelData.getElementsByTagName("Hotel");
                    listEl.innerHTML += `<br><table id="hotel-results" style="width: 100%;">
                                                <tr style="text-align: left;">
                                                    <th colspan="6" style="padding-left: 10px;"><h3 style="line-height: 0.5px;">Available Hotels</h3></th>
                                                </tr>
                                                <tr style="text-align: left; width: 100%; height: 25px; background-color: gainsboro; padding-left: 10px;">
                                                    <th style="width: 45px; padding-left: 10px;">ID</th>
                                                    <th style="width: 175px; padding-left: 10px;">Name</th>
                                                    <th style="width: 75px; padding-left: 10px;">City</th>
                                                    <th style="width: 135px; padding-left: 10px;">Check-In Date</th>
                                                    <th style="width: 135px; padding-left: 10px;">Check-Out Date</th>
                                                    <th style="width: 105px; padding-left: 10px;">Price Per Night</th>
                                                    <th style="background-color: white; width: 120px; padding-left: 10px;"></th>
                                                </tr>
                                             </table>`;
                    for (const hotel of hotelList) {
                        if (hotel.getElementsByTagName("city")[0].firstChild.data.toLowerCase() === city.toLowerCase()) {
                            const id = hotel.getAttribute("id");
                            const name = hotel.getElementsByTagName("hotelName")[0].firstChild.data;
                            const hotelCity = hotel.getElementsByTagName("city")[0].firstChild.data;
                            const numRoomsAvailable = hotel.getElementsByTagName("numAvailableRooms")[0].firstChild.data;
                            const checkInDate = checkIn.toDateString();
                            const checkOutDate = checkOut.toDateString();
                            const price = hotel.getElementsByTagName("pricePerNight")[0].firstChild.data;
                            
                            let tableRef = document.getElementById("hotel-results");
                            let result = document.createElement("tr");
                            
                            let idCol = document.createElement("td");
                            idCol.textContent = id;
                            idCol.style.paddingLeft = "10px";
                            result.appendChild(idCol);
                            
                            let nameCol = document.createElement("td");
                            nameCol.textContent = name;
                            nameCol.style.paddingLeft = "10px";
                            nameCol.style.fontWeight = "bold";
                            result.appendChild(nameCol);
                            
                            let cityCol = document.createElement("td");
                            cityCol.textContent = hotelCity;
                            cityCol.style.paddingLeft = "10px";
                            result.appendChild(cityCol);
                            
                            let checkInCol = document.createElement("td");
                            checkInCol.textContent = checkInDate;
                            checkInCol.style.paddingLeft = "10px";
                            result.appendChild(checkInCol);
                            
                            let checkOutCol = document.createElement("td");
                            checkOutCol.textContent = checkOutDate;
                            checkOutCol.style.paddingLeft = "10px";
                            result.appendChild(checkOutCol);
                            
                            let priceCol = document.createElement("td");
                            priceCol.textContent = "$" + price;
                            priceCol.style.paddingLeft = "10px";
                            priceCol.style.fontWeight = "bold";
                            priceCol.style.backgroundColor = '#F0F0F0';
                            result.appendChild(priceCol);
                            
                            let bookCol = document.createElement("td");
                            const myButton = document.createElement('button');
                            myButton.innerText = 'Add to Cart';
                            myButton.addEventListener('click', () => {
                                const cart = {
                                    hotel: { id: id, name: name, city: hotelCity, num_rooms_needed: roomsNeeded, num_rooms_available: numRoomsAvailable, pricePerNight: price}, 
                                    checkIn_date: checkIn.toDateString(),
                                    checkOut_date: checkOut.toDateString(),
                                    passengers: { adults, children, infants },
                                };
                                try {
                                    sessionStorage.setItem("hotels_cart", JSON.stringify(cart));
                                } catch { }
                                window.location.href = "cart.html";
                            });
                            bookCol.appendChild(myButton);
                            bookCol.style.paddingLeft = "10px";
                            result.appendChild(bookCol);
                            tableRef.appendChild(result);
                            /* result += `<tr style="height: 22px;">
                                            <td style="padding-left: 10px;">${id}</td>
                                            <td style="font-weight: bold; padding-left: 10px;">${name}</td>
                                            <td style="padding-left: 10px;">${hotelCity}</td>
                                            <td style="padding-left: 10px;">${checkInDate}</td>
                                            <td style="padding-left: 10px;">${checkOutDate}</td>
                                            <td style="font-weight: bold; background-color: #F0F0F0; padding-left: 10px;">$${price}</td>
                                            <td style="padding-left: 10px;"><button id="cart">Add to Cart</button></td>
                                       </tr>`; */
                        }
                    }
                }
            }
        });
    }
});

