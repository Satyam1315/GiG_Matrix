<?php
session_start();
include 'db_config.php';

// Only check if user is logged in, don't redirect
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GigHub</title>
    <link rel="stylesheet" href="browse-jobs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="nav-container">
            <div class="logo">
                <?php if ($isLoggedIn): ?>
                    <a href="freelancer-dashboard.php">GiG Matrix</a>
                <?php else: ?>
                    <a href="index.html">GiG Matrix</a>
                <?php endif; ?>
            </div>
            <nav class="nav-links">
                <?php if ($isLoggedIn): ?>
                    <a href="freelancer-dashboard.php" class="nav-link">Home</a>
                <?php else: ?>
                    <a href="index.html" class="nav-link">Home</a>
                <?php endif; ?>
                <a href="browse-jobs.php" class="nav-link active">Browse Jobs</a>
                <!-- <a href="#" class="nav-link">Categories</a> -->
                <?php if ($isLoggedIn): ?>
                    <a href="messages.php" class="nav-link">Messages</a>
                <?php endif; ?>
            </nav>
            
            <div class="auth-buttons">
                <?php if ($isLoggedIn): ?>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                    </div>
                    <a href="logout.php" class="btn btn-logout">Logout</a>
                <?php else: ?>
                    <a href="login.html" class="btn btn-login">Login</a>
                    <a href="signup.html" class="btn btn-signup">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="browse-jobs-page animate">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header animate">
                <h1>Find Your Dream Job</h1>
                <p>Browse through thousands of job opportunities and find the perfect match for your skills</p>
            </div>

            <!-- Search and Filters -->
            <section class="search-section animate">
                <div class="search-container">
                    <div class="search-box">
                        <input type="text" id="jobSearch" placeholder="Search jobs by title, company, or skills...">
                        <button onclick="searchJobs()"><i class="fas fa-search"></i></button>
                    </div>
                    <div class="filters">
                        <select id="categoryFilter">
                            <option value="">All Categories</option>
                            <option value="web">Web Development</option>
                            <option value="mobile">Mobile Development</option>
                            <option value="design">Design</option>
                            <option value="marketing">Marketing</option>
                        </select>
                        <select id="experienceFilter">
                            <option value="">Experience Level</option>
                            <option value="entry">Entry Level</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="expert">Expert</option>
                        </select>
                        <select id="jobTypeFilter">
                            <option value="">Job Type</option>
                            <option value="full-time">Full Time</option>
                            <option value="part-time">Part Time</option>
                            <option value="contract">Contract</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Job Listings -->
            <section class="job-listings animate">
                <div class="section-header">
                    <h2>Available Jobs</h2>
                    <p id="jobCount">Showing <span>0</span> jobs</p>
                </div>
                <div class="jobs-grid" id="jobsGrid">
                    <?php
                    // Fetch jobs from database
                    $query = "SELECT j.*, u.username as client_name 
                             FROM jobs j 
                             JOIN users u ON j.developer_id = u.id 
                             WHERE j.status = 'open' 
                             ORDER BY j.created_at DESC";
                    $result = mysqli_query($conn, $query);

                    if (mysqli_num_rows($result) > 0) {
                        while ($job = mysqli_fetch_assoc($result)) {
                            $skills = explode(',', $job['required_skills']);
                            ?>
                            <div class="job-card" data-id="<?php echo $job['id']; ?>">
                                <div class="job-header">
                                    <div class="company-info">
                                        <!-- <img src="https://via.placeholder.com/50" alt="Company Logo"> -->
                                        <div>
                                            <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                                            <p><?php echo htmlspecialchars($job['client_name']); ?></p>
                                        </div>
                                    </div>
                                    <span class="job-type"><?php echo htmlspecialchars($job['category']); ?></span>
                                </div>
                                <div class="job-details">
                                    <p><i class="fas fa-dollar-sign"></i> Budget: $<?php echo number_format($job['budget'], 2); ?></p>
                                    <p><i class="fas fa-clock"></i> Posted: <?php echo date('M d, Y', strtotime($job['created_at'])); ?></p>
                                </div>
                                <div class="job-description">
                                    <p><?php echo htmlspecialchars($job['description']); ?></p>
                                </div>
                                <div class="job-skills">
                                    <?php foreach ($skills as $skill): ?>
                                        <span><?php echo htmlspecialchars(trim($skill)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="job-actions">
                                    <?php if ($isLoggedIn): ?>
                                        <button class="btn-apply" onclick="applyForJob(<?php echo $job['id']; ?>)">Apply Now</button>
                                    <?php else: ?>
                                        <button class="btn-apply" onclick="showLoginModal()">Apply Now</button>
                                    <?php endif; ?>
                                    <button class="btn-save"><i class="far fa-bookmark"></i> Save</button>
                                </div>

                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="no-jobs">No jobs available at the moment.</p>';
                    }
                    ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="animate">
        <div class="footer-content">
            <div class="footer-section">
                <h3>About Us</h3>
                <p>Connecting talented freelancers with businesses worldwide. Find your next opportunity or hire the best talent.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="browse-jobs.php">Browse Jobs</a></li>
                    <!-- <li><a href="#">Categories</a></li> -->
                    <?php if ($isLoggedIn): ?>
                        <li><a href="messages.php">Messages</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-section">
                <h3>For Talents</h3>
                <ul class="footer-links">
                    <!-- <li><a href="#">How It Works</a></li> -->
                    <li><a href="signup.html">Create Profile</a></li>
                    <li><a href="browse-jobs.php">Find Jobs</a></li>
                    <!-- <li><a href="login.php">Post Jobs</a></li> -->
                    <li><a href="#">Success Stories</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <ul class="contact-info">
                    <li><i class="fas fa-envelope"></i> support@freelancehub.com</li>
                    <li><i class="fas fa-phone"></i> +1 (555) 123-4567</li>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Main St, City, Country</li>
                </ul>
            </div>
        </div>
        <div class="social-links">
            <a href="https://www.facebook.com" class="social-link"><i class="fab fa-facebook-f"></i></a>
            <a href="https://www.twitter.com" class="social-link"><i class="fab fa-twitter"></i></a>
            <a href="https://www.linkedin.com" class="social-link"><i class="fab fa-linkedin-in"></i></a>
            <a href="https://www.instagram.com" class="social-link"><i class="fab fa-instagram"></i></a>
            <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> FreelanceHub. All rights reserved.</p>
            <div class="legal-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Cookie Policy</a>
            </div>
        </div>
    </footer>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <i class="fas fa-user-circle"></i>
                <h2>Join to Apply</h2>
                <p>Create an account or log in to apply for this job. It only takes a few minutes!</p>
            </div>
            <div class="modal-actions">
                <a href="login.html" class="btn btn-primary">Login</a>
                <a href="signup.html" class="btn btn-secondary">Sign Up</a>
            </div>
            <p class="modal-footer-text">
                By joining, you agree to our Terms of Service and Privacy Policy
            </p>
        </div>
    </div>

    <!-- Application Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Application Submitted</h2>
            <p>Your application has been successfully submitted!</p>
            <div class="modal-actions">
                <button class="btn btn-primary" onclick="closeSuccessModal()">OK</button>
            </div>
        </div>
    </div>

    <!-- Add error message container -->
    <div id="errorContainer" class="error-message"></div>

    <script>
        // Function to show login modal
        function showLoginModal() {
            document.getElementById('loginModal').style.display = 'block';
        }

        // Function to close login modal
        function closeLoginModal() {
            document.getElementById('loginModal').style.display = 'none';
        }

        // Function to show success modal
        function showSuccessModal() {
            document.getElementById('successModal').style.display = 'block';
        }

        // Function to close success modal
        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
        }

        // Function to apply for a job
        function applyForJob(jobId) {
            fetch('apply-job.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    job_id: jobId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal();
                    // Disable the apply button for this job
                    const applyButton = document.querySelector(`.job-card[data-id="${jobId}"] .btn-apply`);
                    if (applyButton) {
                        applyButton.disabled = true;
                        applyButton.textContent = 'Applied';
                        applyButton.classList.add('applied');
                    }
                } else {
                    document.getElementById('errorContainer').textContent = data.message;
                    document.getElementById('errorContainer').style.display = 'block';
                    if (data.message.includes('Please login')) {
                        setTimeout(() => {
                            showLoginModal();
                        }, 1500);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('errorContainer').textContent = 'An error occurred. Please try again.';
                document.getElementById('errorContainer').style.display = 'block';
            })
            .finally(() => {
                setTimeout(() => {
                    document.getElementById('errorContainer').style.display = 'none';
                }, 5000);
            });
        }

        // Close modals when clicking the X
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.onclick = function() {
                this.closest('.modal').style.display = 'none';
            }
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Function to search jobs
        function searchJobs() {
            const searchTerm = document.getElementById('jobSearch').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            const experience = document.getElementById('experienceFilter').value;
            const jobType = document.getElementById('jobTypeFilter').value;

            const jobCards = document.querySelectorAll('.job-card');
            let visibleCount = 0;

            jobCards.forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const company = card.querySelector('.company-info p').textContent.toLowerCase();
                const skills = Array.from(card.querySelectorAll('.job-skills span')).map(span => span.textContent.toLowerCase());
                const jobCategory = card.querySelector('.job-type').textContent.toLowerCase();

                const matchesSearch = title.includes(searchTerm) ||
                                    company.includes(searchTerm) ||
                                    skills.some(skill => skill.includes(searchTerm));
                const matchesCategory = !category || jobCategory.includes(category);
                const matchesExperience = !experience || card.dataset.experience === experience;
                const matchesJobType = !jobType || card.dataset.type === jobType;

                if (matchesSearch && matchesCategory && matchesExperience && matchesJobType) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            document.getElementById('jobCount').querySelector('span').textContent = visibleCount;
        }

        // Add event listeners to filters
        document.getElementById('categoryFilter').addEventListener('change', searchJobs);
        document.getElementById('experienceFilter').addEventListener('change', searchJobs);
        document.getElementById('jobTypeFilter').addEventListener('change', searchJobs);
        document.getElementById('jobSearch').addEventListener('input', searchJobs);

        // Initialize job count
        document.getElementById('jobCount').querySelector('span').textContent = 
            document.querySelectorAll('.job-card').length;
    </script>
</body>
</html></html>
