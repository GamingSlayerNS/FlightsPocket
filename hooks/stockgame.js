document.addEventListener("DOMContentLoaded", () => {
    const userMoneySpan = document.getElementById("user-money");
    const stockChartCanvas = document.getElementById("stock-chart");
    const companySelect = document.getElementById("company-select");
    const investmentAmountInput = document.getElementById("investment-amount");
    const investButton = document.getElementById("invest-button");
    const nextDayButton = document.getElementById("next-day-button");
    const skip10DaysButton = document.getElementById("skip-10-days-button");
    const gameMessage = document.getElementById("game-message");
    const cashOutButton = document.getElementById("cash-out-button");

    if (!stockChartCanvas) return;

    let userMoney = 10;
    let currentInvestment = { companyIndex: null, shares: 0, amountInvested: 0, entryPrice: null };

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

    // Helper to get last price of a company
    function getLastPrice(companyIndex) {
        const history = stockHistory[companyIndex];
        return history[history.length - 1];
    }

    function updateDisplay() {
        userMoneySpan.textContent = userMoney.toFixed(2);
        stockChart.update();
    }

    function simulateDay() {
        // Capture prices before the day advances
        const lastPrices = stockHistory.map((history) => history[history.length - 1]);

        // Update stock prices for all companies (no auto cash-out)
        stockHistory.forEach((history) => {
            const currentPrice = history[history.length - 1];
            const changePercent = (Math.random() - 0.45) * 0.5; // Increased volatility
            const newPrice = Math.max(1, currentPrice * (1 + changePercent));
            history.push(newPrice);
        });

        // Message based on current position (unrealized P/L only)
        if (currentInvestment.shares > 0) {
            const idx = currentInvestment.companyIndex;
            const prev = lastPrices[idx];
            const now = getLastPrice(idx);
            const currentValue = currentInvestment.shares * now;
            const pl = currentValue - currentInvestment.amountInvested;
            gameMessage.textContent = `${companies[idx].name} changed from $${prev.toFixed(2)} to $${now.toFixed(
                2
            )}. Investment value $${currentValue.toFixed(2)} (P/L $${pl.toFixed(2)}). You're still invested.`;
        } else {
            gameMessage.textContent = "A new day has passed. Check the stock prices and make an investment!";
        }

        // Update chart
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
        if (currentInvestment.shares > 0) {
            gameMessage.textContent = "You already have an active investment. Cash out before investing again.";
            return;
        }

        const entryPrice = getLastPrice(companyIndex);
        const shares = amount / entryPrice;

        userMoney -= amount;
        currentInvestment = { companyIndex, shares, amountInvested: amount, entryPrice };
        gameMessage.textContent = `You bought ${shares.toFixed(4)} shares of ${
            companies[companyIndex].name
        } at $${entryPrice.toFixed(2)}. Click 'Next Day' or 'Skip 10 Days'. Use 'Cash Out' when ready.`;
        investmentAmountInput.value = "";
        updateDisplay();
    });

    nextDayButton.addEventListener("click", () => {
        simulateDay();
    });

    if (skip10DaysButton) {
        skip10DaysButton.addEventListener("click", () => {
            let lastDayMessage = "";
            const hadInvestment = currentInvestment.shares > 0;
            let startValue = 0;
            let idx = currentInvestment.companyIndex;
            if (hadInvestment) {
                startValue = currentInvestment.shares * getLastPrice(idx);
            }
            for (let i = 0; i < 10; i++) {
                simulateDay();
                if (i === 9) {
                    lastDayMessage = gameMessage.textContent;
                }
            }
            if (hadInvestment) {
                const endValue = currentInvestment.shares * getLastPrice(idx);
                const diff = endValue - startValue;
                gameMessage.textContent = `${lastDayMessage} Over 10 days, your investment value changed by $${diff.toFixed(
                    2
                )}. You're still invested.`;
            } else {
                gameMessage.textContent = `${lastDayMessage} Over 10 days, no active investment was held.`;
            }
        });
    }

    // Optional cash-out handler (only realizes gains when user clicks)
    if (cashOutButton) {
        cashOutButton.addEventListener("click", () => {
            if (currentInvestment.shares <= 0) {
                gameMessage.textContent = "No active investment to cash out.";
                return;
            }
            const idx = currentInvestment.companyIndex;
            const price = getLastPrice(idx);
            const value = currentInvestment.shares * price;
            const pl = value - currentInvestment.amountInvested;

            userMoney += value;
            currentInvestment = { companyIndex: null, shares: 0, amountInvested: 0, entryPrice: null };

            gameMessage.textContent = `You cashed out ${companies[idx].name} at $${price.toFixed(
                2
            )} and received $${value.toFixed(2)} (P/L $${pl.toFixed(2)}).`;
            updateDisplay();
        });
    }

    updateDisplay();
});
