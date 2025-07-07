    <!-- Footer -->
    <footer class="bg-dark text-white pt-5 pb-3">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Products</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <ul class="list-unstyled">
                                <?php
                                $product_links = get_product_links();
                                foreach(array_slice($product_links, 0, 3) as $link) {
                                    echo '<li><a href="'.$link['url'].'" class="text-white text-decoration-none">'.$link['name'].'</a></li>';
                                }
                                ?>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <ul class="list-unstyled">
                                <?php
                                foreach(array_slice($product_links, 3, 3) as $link) {
                                    echo '<li><a href="'.$link['url'].'" class="text-white text-decoration-none">'.$link['name'].'</a></li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5>Customer Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <?php
                                $customer_links = get_customer_links();
                                foreach(array_slice($customer_links, 0, 5) as $link) {
                                    echo '<li><a href="'.$link['url'].'" class="text-white text-decoration-none">'.$link['name'].'</a></li>';
                                }
                                ?>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <?php
                                foreach(array_slice($customer_links, 5, 5) as $link) {
                                    echo '<li><a href="'.$link['url'].'" class="text-white text-decoration-none">'.$link['name'].'</a></li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row">
                <div class="col-md-4">
                    <h6>Head Office</h6>
                    <p><?php echo SITE_NAME; ?><br>
                    Plessey House, 1st Floor<br>
                    Nairobi, Kenya<br>
                    P.O Box 62743-00200</p>
                </div>
                <div class="col-md-4">
                <h6>Connect With Us</h6>
                    <div class="d-flex flex-wrap">  <!-- Added flex container -->
                    <a href="#" class="text-white me-3"><i class="fab fa-facebook-f fa-lg"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-linkedin-in fa-lg"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-youtube fa-lg"></i></a>
                </div>
                </div>
                <div class="col-md-4">
                    <h6>Payment Methods</h6>
                    <img src="assets/images/payment/mpesa.png" height="30" class="me-2">
                    <img src="assets/images/payment/visa.png" height="30" class="me-2">
                    <img src="assets/images/payment/mastercard.png" height="30" class="me-2">
                    <img src="assets/images/payment/bank-transfer.png" height="30">
                </div>
            </div>
            
            <div class="text-center mt-3">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- WhatsApp Float Button - Bottom Right Corner -->
<div class="whatsapp-float">
    <a href="https://wa.me/254743392675" class="whatsapp-link" target="_blank" rel="noopener noreferrer">
        <i class="fab fa-whatsapp"></i>
        <span class="whatsapp-tooltip">Chat with us on WhatsApp</span>
    </a>
</div>

<!-- Add this CSS to your stylesheet -->
<style>
    /* WhatsApp Floating Button */
    .whatsapp-float {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 1000;
    }
    
    .whatsapp-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        background-color: #25D366;
        color: white;
        border-radius: 50%;
        text-align: center;
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
        font-size: 30px;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .whatsapp-link:hover {
        background-color: #128C7E;
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(37, 211, 102, 0.4);
        text-decoration: none;
        color: white;
    }
    
    .whatsapp-tooltip {
        position: absolute;
        right: 70px;
        width: max-content;
        background: #333;
        color: #fff;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 14px;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .whatsapp-link:hover .whatsapp-tooltip {
        opacity: 1;
        visibility: visible;
        right: 75px;
    }
    
    /* Animation for attention */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .whatsapp-link {
        animation: pulse 2s infinite;
    }
    
    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .whatsapp-float {
            bottom: 20px;
            right: 20px;
        }
        
        .whatsapp-link {
            width: 50px;
            height: 50px;
            font-size: 25px;
        }
        
        .whatsapp-tooltip {
            display: none;
        }
    }
</style>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>