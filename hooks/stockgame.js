document.addEventListener("DOMContentLoaded", () => {
    const userMoneySpan = document.getElementById("user-money");
    const stockChartCanvas = document.getElementById("stock-chart");
    const companySelect = document.getElementById("company-select");
    const investmentAmountInput = document.getElementById("investment-amount");
    const investButton = document.getElementById("invest-button");
    const nextDayButton = document.getElementById("next-day-button");
    const skip10DaysButton = document.getElementById("skip-10-days-button");
    const gameMessage = document.getElementById("game-message");

    if (!stockChartCanvas) return;

    let userMoney = 10;
    let currentInvestment = { companyIndex: null, amount: 0 };

    const companies = [
        { name: "FlightCorp", color: "rgba(255, 99, 132, 1)" },
        { name: "StayStonks", color: "rgba(54, 162, 235, 1)" },
        { name: "CarGo", color: "rgba(255, 206, 86, 1)" },
        { name: "CruiseLine", color: "rgba(75, 192, 192, 1)" },
    ];

    // Initialize stock history with some random data
    let stockHistory = companies.map(() => [100 + Math.random() * 20]);

    const chartData = {
        labels: ["Day 1"],
        datasets: companies.map((company, index) => ({
            label: company.name,
            data: stockHistory[index],
            borderColor: company.color,
            tension: 0.1,
            fill: false,
        })),
    };

    const stockChart = new Chart(stockChartCanvas, {
        type: "line",
        data: chartData,
        options: {
            scales: {
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: "Stock Price ($)",
                    },
                },
                x: {
                    title: {
                        display: true,
                        text: "Day",
                    },
                },
            },
        },
    });

    function updateDisplay() {
        userMoneySpan.textContent = userMoney.toFixed(2);
        stockChart.update();
    }

    function simulateDay() {
        // 1. Calculate investment result
        if (currentInvestment.amount > 0) {
            const companyIndex = currentInvestment.companyIndex;
            const lastPrice = stockHistory[companyIndex][stockHistory[companyIndex].length - 1];

            // 2. Update stock prices for all companies
            stockHistory.forEach((history, index) => {
                const currentPrice = history[history.length - 1];
                const changePercent = (Math.random() - 0.45) * 0.5; // Increased volatility
                const newPrice = Math.max(1, currentPrice * (1 + changePercent)); // Prevent stock from going to 0 or negative
                history.push(newPrice);
            });

            const newPriceOfInvestedStock = stockHistory[companyIndex][stockHistory[companyIndex].length - 1];
            const growth = newPriceOfInvestedStock / lastPrice;
            const returnedAmount = currentInvestment.amount * growth;
            userMoney += returnedAmount;

            gameMessage.textContent = `${companies[companyIndex].name} stock changed from $${lastPrice.toFixed(
                2
            )} to $${newPriceOfInvestedStock.toFixed(2)}. You got back $${returnedAmount.toFixed(2)}.`;
            currentInvestment = { companyIndex: null, amount: 0 };
        } else {
            // Just update stock prices if no investment was made
            stockHistory.forEach((history) => {
                const currentPrice = history[history.length - 1];
                const changePercent = (Math.random() - 0.45) * 0.5; // Increased volatility
                const newPrice = Math.max(1, currentPrice * (1 + changePercent));
                history.push(newPrice);
            });
            gameMessage.textContent = "A new day has passed. Check the stock prices and make an investment!";
        }

        // 3. Update chart
        stockChart.data.labels.push(`Day ${stockChart.data.labels.length + 1}`);
        stockChart.data.datasets.forEach((dataset, index) => {
            dataset.data = stockHistory[index];
        });

        updateDisplay();
    }

    investButton.addEventListener("click", () => {
        const companyIndex = parseInt(companySelect.value);
        const amount = parseFloat(investmentAmountInput.value);

        if (isNaN(amount) || amount <= 0) {
            gameMessage.textContent = "Please enter a valid investment amount.";
            return;
        }
        if (amount > userMoney) {
            gameMessage.textContent = "You don't have enough money to make that investment.";
            return;
        }
        if (currentInvestment.amount > 0) {
            gameMessage.textContent = "You must wait for the next day to change your investment.";
            return;
        }

        userMoney -= amount;
        currentInvestment = { companyIndex, amount };
        gameMessage.textContent = `You invested $${amount.toFixed(2)} in ${
            companies[companyIndex].name
        }. Click 'Next Day' to see what happens!`;
        investmentAmountInput.value = "";
        updateDisplay();
    });

    nextDayButton.addEventListener("click", () => {
        simulateDay();
    });

    if (skip10DaysButton) {
        skip10DaysButton.addEventListener("click", () => {
            const moneyBefore = userMoney;
            let lastDayMessage = "";
            for (let i = 0; i < 10; i++) {
                simulateDay();
                if (i === 9) {
                    lastDayMessage = gameMessage.textContent;
                }
            }
            const difference = userMoney - moneyBefore;
            gameMessage.textContent = `${lastDayMessage} Over 10 days, your money changed by $${difference.toFixed(
                2
            )}.`;
        });
    }

    updateDisplay();
});
