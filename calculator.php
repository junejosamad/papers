<?php
require_once 'config.php';
requireLogin();

// Get user info (for header consistency)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPA Calculator - Bahria Papers Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/darkmode.js"></script>
    <style>
        .calculator-card {
            background: var(--md-surface);
            border-radius: 20px;
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .calc-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 40px;
            gap: 15px;
            margin-bottom: 15px;
            align-items: center;
        }

        .calc-header {
            font-weight: 500;
            color: var(--md-primary);
            margin-bottom: 15px;
        }

        .gpa-result {
            margin-top: 30px;
            text-align: center;
            padding: 20px;
            background: var(--md-surface-variant);
            border-radius: 15px;
            display: none;
        }

        .gpa-score {
            font-size: 48px;
            font-weight: 700;
            color: var(--md-primary);
        }
    </style>
</head>

<body class="dashboard-body">

    <div class="header">
        <div class="logo">
            <span class="material-icons">school</span>
            <div class="logo-text">
                <h1>Bahria University</h1>
                <p>Past Papers Portal</p>
            </div>
        </div>
        <div class="user-info">
            <button class="btn btn-secondary" onclick="toggleTheme()" title="Toggle Dark/Light Mode"
                style="padding: 8px 12px; min-width: auto;">
                <span class="material-icons" id="theme-icon">dark_mode</span>
            </button>
            <a href="dashboard.php" class="btn btn-secondary">
                <span class="material-icons">arrow_back</span>
                Back to Dashboard
            </a>
        </div>
    </div>

    <div class="container">
        <div class="section-header" style="justify-content: center; margin-bottom: 30px;">
            <h2>
                <span class="material-icons"
                    style="color: var(--md-primary); vertical-align: middle; margin-right: 10px;">calculate</span>
                Semester GPA Calculator
            </h2>
        </div>

        <div class="calculator-card">
            <div class="calc-row calc-header">
                <div>Course Name (Optional)</div>
                <div>Credit Hours</div>
                <div>Grade</div>
                <div></div>
            </div>

            <div id="courseRows">
                <!-- Rows will be added here -->
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button class="btn btn-secondary" onclick="addRow()">
                    <span class="material-icons">add</span>
                    Add Course
                </button>
                <button class="btn btn-primary" onclick="calculateGPA()" style="flex: 1;">
                    <span class="material-icons">functions</span>
                    Calculate GPA
                </button>
            </div>

            <div id="resultBox" class="gpa-result">
                <h4>Your Semester GPA</h4>
                <div class="gpa-score" id="gpaValue">0.00</div>
                <p id="gpaMessage" style="margin-top: 10px; color: #666;"></p>
            </div>
        </div>
    </div>

    <script>
        const gradePoints = {
            'A': 4.00, 'A-': 3.67,
            'B+': 3.33, 'B': 3.00, 'B-': 2.67,
            'C+': 2.33, 'C': 2.00, 'C-': 1.67,
            'D+': 1.33, 'D': 1.00,
            'F': 0.00
        };

        function addRow() {
            const div = document.createElement('div');
            div.className = 'calc-row';
            div.innerHTML = `
                <input type="text" class="input-field" placeholder="e.g. Data Structures">
                <select class="input-field credit-hours">
                    <option value="3" selected>3</option>
                    <option value="4">4</option>
                    <option value="2">2</option>
                    <option value="1">1</option>
                </select>
                <select class="input-field grade">
                    <option value="A">A (4.00)</option>
                    <option value="A-">A- (3.67)</option>
                    <option value="B+">B+ (3.33)</option>
                    <option value="B">B (3.00)</option>
                    <option value="B-">B- (2.67)</option>
                    <option value="C+">C+ (2.33)</option>
                    <option value="C">C (2.00)</option>
                    <option value="C-">C- (1.67)</option>
                    <option value="D+">D+ (1.33)</option>
                    <option value="D">D (1.00)</option>
                    <option value="F">F (0.00)</option>
                </select>
                <button class="btn-icon" onclick="this.parentElement.remove()" style="color: #f44336;">
                    <span class="material-icons">delete</span>
                </button>
            `;
            document.getElementById('courseRows').appendChild(div);
        }

        function calculateGPA() {
            let totalPoints = 0;
            let totalCredits = 0;

            const rows = document.querySelectorAll('.calc-row:not(.calc-header)');

            rows.forEach(row => {
                const credits = parseFloat(row.querySelector('.credit-hours').value);
                const grade = row.querySelector('.grade').value;
                const points = gradePoints[grade];

                totalPoints += (points * credits);
                totalCredits += credits;
            });

            if (totalCredits === 0) {
                alert("Please add at least one course.");
                return;
            }

            const gpa = totalPoints / totalCredits;
            const gpaFormatted = gpa.toFixed(2);

            const resultBox = document.getElementById('resultBox');
            const gpaValue = document.getElementById('gpaValue');
            const gpaMessage = document.getElementById('gpaMessage');

            resultBox.style.display = 'block';
            gpaValue.textContent = gpaFormatted;

            if (gpa >= 3.5) gpaMessage.textContent = "Excellent work! Dean's List material.";
            else if (gpa >= 3.0) gpaMessage.textContent = "Great job! Keep it up.";
            else if (gpa >= 2.0) gpaMessage.textContent = "Good effort. Push harder next time!";
            else gpaMessage.textContent = "Focus on your studies. You can improve this!";

            // Scroll to result
            resultBox.scrollIntoView({ behavior: 'smooth' });
        }

        // Add 5 initial rows
        for (let i = 0; i < 5; i++) addRow();
    </script>
</body>

</html>