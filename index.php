<?php require_once __DIR__ . '/includes/header.php'; ?>
<style>
@keyframes heroSlide {
    0% { transform: translateX(110%); opacity: 0; color: #ef5a1b; }
    10% { opacity: 1; color: #ef5a1b; }
    35% { transform: translateX(0); color: #0ebdaf; }
    70% { color: #ff0ba1; }
    100% { transform: translateX(-110%); opacity: 0; color: #ef5a1b; }
}
.hero-title {
    display: block;
    margin: 1.5rem 0 1rem;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 800;
    line-height: 1.2;
    animation: heroSlide 10s linear infinite;
    text-shadow: 0 2px 10px rgba(16, 244, 248, 0.18);
    white-space: nowrap;
    overflow: visible;
    width: max-content;
    max-width: 100%;
}
</style>
<section>
    <h1 class="hero-title">FoodLink makes food ordering simple, fast and reliable created by Pius😁</h1>
</section>
<section class="hero">
    <div>
       
        <p>FoodLink is a web-based food ordering and delivery platform that connects customers with restaurants in their area. Customers can create accounts, set delivery locations, browse menus, schedule meals, place orders, pay online, and track delivery progress.</p>
        <div class="stats">
            <div class="stat"><strong>Easy registration</strong><br>Create an account in minutes</div>
            <div class="stat"><strong>Location-based discovery</strong><br>Find nearby restaurants quickly</div>
            <div class="stat"><strong>Fast ordering</strong><br>Place and track orders online</div>
        </div>
        <div style="margin-top:1rem;display:flex;gap:.75rem;flex-wrap:wrap;">
            <a class="btn btn-primary" href="register.php">Create Account</a>
            <a class="btn btn-secondary" href="login.php">Login</a>
        </div>
    </div>
    <div class="hero-card">
        <h3>What customers can do</h3>
        <ul>
            <li>Update personal profiles</li>
            <li>Register delivery locations</li>
            <li>Browse restaurant menus</li>
            <li>Schedule daily or weekly meals</li>
            <li>Place secure food orders</li>
            <li>Track order delivery status</li>
        </ul>
    </div>
</section>

<section class="grid grid-3">
    <div class="card">
        <h3>Customer portal</h3>
        <p>Customers can manage profiles, place food orders, and review their order history from a single dashboard.</p>
    </div>
    <div class="card">
        <h3>Secure ordering</h3>
        <p>Customers can place secure online food orders and track delivery updates from their accounts.</p>
    </div>
    <div class="card">
        <h3>Reliable service</h3>
        <p>The platform supports meal scheduling, recurring orders, and smooth delivery coordination.</p>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
