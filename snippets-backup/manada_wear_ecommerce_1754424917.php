<?php
/*
 * Código generado por DrawCode AI
 * Shortcode: [manada_wear_ecommerce]
 * Fecha: 2025-08-05 20:15:17
 * Timestamp: 1754424917
 * User ID: 0
 */

/**
 * Plugin Name: Manada Wear E-commerce
 * Description: E-commerce para Manada Wear - Bordados personalizados de tu mascota
 * Version: 1.0
 * Author: Manada Wear
 */

// Shortcode para mostrar el e-commerce
function manada_wear_ecommerce_shortcode() {
    ob_start();
    ?>
    <div class="manada-v1-ecommerce-container">
        <!-- Hero Section -->
        <section class="manada-v1-hero">
            <div class="manada-v1-hero-content">
                <h1 class="manada-v1-title">Manada Wear</h1>
                <p class="manada-v1-subtitle">Bordados personalizados de tu mascota</p>
                <p class="manada-v1-description">Bordamos la carita de tu mascota en camisetas, hoodies, gorras y más.</p>
                <a href="#manada-v1-products" class="manada-v1-button">Ver productos</a>
            </div>
        </section>

        <!-- Proceso Section -->
        <section class="manada-v1-process">
            <h2 class="manada-v1-section-title">TU PRENDA PERSONALIZADA FAVORITA EN 4 PASOS</h2>
            <div class="manada-v1-process-container">
                <div class="manada-v1-process-item">
                    <div class="manada-v1-process-number">1</div>
                    <p>Escoge tu prenda favorita en nuestra web y carga las fotos de tu peludo.</p>
                </div>
                <div class="manada-v1-process-item">
                    <div class="manada-v1-process-number">2</div>
                    <p>Crearemos una ilustración única para ti y la enviaremos a tu WhatsApp para que realices revisiones.</p>
                </div>
                <div class="manada-v1-process-item">
                    <div class="manada-v1-process-number">3</div>
                    <p>Bordamos tu prenda, la abuela perruna es la encargada de hacer este proceso.</p>
                </div>
                <div class="manada-v1-process-item">
                    <div class="manada-v1-process-number">4</div>
                    <p>¡Tu prenda está lista! La empacaremos con mucho amor y te compartiremos la guía para que puedas hacer seguimiento.</p>
                </div>
            </div>
        </section>

        <!-- Products Section -->
        <section id="manada-v1-products" class="manada-v1-products">
            <h2 class="manada-v1-section-title">NUEVA COLECCIÓN PET MOM ERA</h2>
            <div class="manada-v1-products-grid">
                <div class="manada-v1-product-card">
                    <div class="manada-v1-product-badge">Sale!</div>
                    <div class="manada-v1-product-image">
                        <img src="https://via.placeholder.com/300x300" alt="Termo Pet Mom Era">
                    </div>
                    <div class="manada-v1-product-info">
                        <h3 class="manada-v1-product-title">Termo In My Pet Mom Era</h3>
                        <p class="manada-v1-product-price">$ 30.000</p>
                        <button class="manada-v1-add-to-cart" data-product-id="termo-pet-mom">Añadir al carrito</button>
                    </div>
                </div>
                <div class="manada-v1-product-card">
                    <div class="manada-v1-product-image">
                        <img src="https://via.placeholder.com/300x300" alt="Tote Bag Pet Mom Era">
                    </div>
                    <div class="manada-v1-product-info">
                        <h3 class="manada-v1-product-title">Tote Bag In My Pet Mom Era</h3>
                        <p class="manada-v1-product-price">$ 30.000</p>
                        <button class="manada-v1-add-to-cart" data-product-id="tote-pet-mom">Añadir al carrito</button>
                    </div>
                </div>
                <div class="manada-v1-product-card">
                    <div class="manada-v1-product-image">
                        <img src="https://via.placeholder.com/300x300" alt="Camiseta Pet Mom Era">
                    </div>
                    <div class="manada-v1-product-info">
                        <h3 class="manada-v1-product-title">Camiseta In My Pet Mom Era</h3>
                        <p class="manada-v1-product-price">$ 70.000</p>
                        <button class="manada-v1-add-to-cart" data-product-id="camiseta-pet-mom">Añadir al carrito</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Personalized Section -->
        <section class="manada-v1-personalized">
            <h2 class="manada-v1-section-title">PERSONALIZA TU PRENDA</h2>
            <p class="manada-v1-personalized-description">Envíanos la foto de tu mascota y nosotros la bordamos en tu prenda favorita</p>
            
            <form id="manada-v1-personalized-form" class="manada-v1-form">
                <div class="manada-v1-form-group">
                    <label for="manada-v1-name">Nombre</label>
                    <input type="text" id="manada-v1-name" name="name" required>
                </div>
                <div class="manada-v1-form-group">
                    <label for="manada-v1-email">Email</label>
                    <input type="email" id="manada-v1-email" name="email" required>
                </div>
                <div class="manada-v1-form-group">
                    <label for="manada-v1-phone">Teléfono / WhatsApp</label>
                    <input type="tel" id="manada-v1-phone" name="phone" required>
                </div>
                <div class="manada-v1-form-group">
                    <label for="manada-v1-product">Producto</label>
                    <select id="manada-v1-product" name="product" required>
                        <option value="">Selecciona un producto</option>
                        <option value="camiseta">Camiseta</option>
                        <option value="hoodie">Hoodie</option>
                        <option value="gorra">Gorra</option>
                        <option value="tote">Tote Bag</option>
                        <option value="termo">Termo</option>
                    </select>
                </div>
                <div class="manada-v1-form-group">
                    <label for="manada-v1-pet-photo">Foto de tu mascota</label>
                    <input type="file" id="manada-v1-pet-photo" name="pet_photo" accept="image/*" required>
                    <p class="manada-v1-form-help">Sube una foto clara de tu mascota para obtener mejores resultados</p>
                </div>
                <div class="manada-v1-form-group">
                    <label for="manada-v1-message">Mensaje adicional</label>
                    <textarea id="manada-v1-message" name="message" rows="4"></textarea>
                </div>
                <button type="submit" class="manada-v1-submit-button">Enviar solicitud</button>
            </form>
        </section>

        <!-- Cart Modal -->
        <div id="manada-v1-cart-modal" class="manada-v1-modal">
            <div class="manada-v1-modal-content">
                <span class="manada-v1-close-modal">&times;</span>
                <h2>Tu carrito</h2>
                <div id="manada-v1-cart-items" class="manada-v1-cart-items">
                    <!-- Cart items will be added here dynamically -->
                </div>
                <div class="manada-v1-cart-total">
                    <p>Total: <span id="manada-v1-cart-total">$ 0</span></p>
                </div>
                <button id="manada-v1-checkout-button" class="manada-v1-checkout-button">Proceder al pago</button>
            </div>
        </div>

        <!-- Success Modal -->
        <div id="manada-v1-success-modal" class="manada-v1-modal">
            <div class="manada-v1-modal-content">
                <span class="manada-v1-close-success-modal">&times;</span>
                <div class="manada-v1-success-message">
                    <h2>¡Solicitud enviada con éxito!</h2>
                    <p>Nos pondremos en contacto contigo pronto a través de WhatsApp para mostrarte tu ilustración personalizada.</p>
                </div>
            </div>
        </div>

        <!-- Cart Icon -->
        <div class="manada-v1-cart-icon">
            <span class="manada-v1-cart-count">0</span>
            <i class="manada-v1-cart-icon-img">🛒</i>
        </div>
    </div>

    <style>
        /* General Styles */
        .manada-v1-ecommerce-container {
            font-family: 'Montserrat', sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0;
            color: #333;
            position: relative;
        }

        .manada-v1-section-title {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 2rem;
            color: #333;
            font-weight: 700;
        }

        .manada-v1-button {
            display: inline-block;
            background-color: #ff6b6b;
            color: white;
            padding: 12px 24px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .manada-v1-button:hover {
            background-color: #ff5252;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Hero Section */
        .manada-v1-hero {
            background-color: #f9f7f7;
            padding: 80px 20px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 50px;
            background-image: linear-gradient(rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.8)), url('https://via.placeholder.com/1200x600');
            background-size: cover;
            background-position: center;
        }

        .manada-v1-title {
            font-size: 3.5rem;
            margin-bottom: 10px;
            color: #ff6b6b;
        }

        .manada-v1-subtitle {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #555;
        }

        .manada-v1-description {
            font-size: 1.1rem;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Process Section */
        .manada-v1-process {
            padding: 50px 20px;
            background-color: #fff;
            margin-bottom: 50px;
        }

        .manada-v1-process-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
        }

        .manada-v1-process-item {
            flex: 1;
            min-width: 220px;
            max-width: 250px;
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            background-color: #f9f7f7;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .manada-v1-process-item:hover {
            transform: translateY(-5px);
        }

        .manada-v1-process-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: #ff6b6b;
            color: white;
            border-radius: 50%;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 15px;
        }

        /* Products Section */
        .manada-v1-products {
            padding: 50px 20px;
            background-color: #fff;
            margin-bottom: 50px;
        }

        .manada-v1-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }

        .manada-v1-product-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            position: relative;
            background-color: white;
        }

        .manada-v1-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .manada-v1-product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #ff6b6b;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .manada-v1-product-image {
            height: 250px;
            overflow: hidden;
        }

        .manada-v1-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .manada-v1-product-card:hover .manada-v1-product-image img {
            transform: scale(1.05);
        }

        .manada-v1-product-info {
            padding: 20px;
        }

        .manada-v1-product-title {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #333;
        }

        .manada-v1-product-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #ff6b6b;
            margin-bottom: 15px;
        }

        .manada-v1-add-to-cart {
            width: 100%;
            padding: 10px;
            background-color: #ff6b6b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .manada-v1-add-to-cart:hover {
            background-color: #ff5252;
        }

        /* Personalized Section */
        .manada-v1-personalized {
            padding: 50px 20px;
            background-color: #f9f7f7;
            border-radius: 10px;
            margin-bottom: 50px;
        }

        .manada-v1-personalized-description {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        .manada-v1-form {
            max-width: 600px;
            margin: 0 auto;
        }

        .manada-v1-form-group {
            margin-bottom: 20px;
        }

        .manada-v1-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .manada-v1-form-group input,
        .manada-v1-form-group select,
        .manada-v1-form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 1rem;
        }

        .manada-v1-form-help {
            font-size: 0.8rem;
            color: #777;
            margin-top: 5px;
        }

        .manada-v1-submit-button {
            background-color: #ff6b6b;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .manada-v1-submit-button:hover {
            background-color: #ff5252;
        }

        /* Modal Styles */
        .manada-v1-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .manada-v1-modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            position: relative;
        }

        .manada-v1-close-modal,
        .manada-v1-close-success-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .manada-v1-cart-items {
            margin: 20px 0;
            max-height: 300px;
            overflow-y: auto;
        }

        .manada-v1-cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .manada-v1-cart-item-info {
            flex: 1;
        }

        .manada-v1-cart-item-title {
            font-weight: 600;
        }

        .manada-v1-cart-item-price {
            color: #ff6b6b;
        }

        .manada-v1-cart-item-remove {
            color: #ff6b6b;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .manada-v1-cart-total {
            font-size: 1.2rem;
            font-weight: bold;
            text-align: right;
            margin: 20px 0;
        }

        .manada-v1-checkout-button {
            width: 100%;
            padding: 12px;
            background-color: #ff6b6b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
        }

        .manada-v1-checkout-button:hover {
            background-color: #ff5252;
        }

        .manada-v1-success-message {
            text-align: center;
        }

        .manada-v1-success-message h2 {
            color: #4CAF50;
            margin-bottom: 15px;
        }

        /* Cart Icon */
        .manada-v1-cart-icon {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #ff6b6b;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 100;
            transition: transform 0.3s ease;
        }

        .manada-v1-cart-icon:hover {
            transform: scale(1.1);
        }

        .manada-v1-cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: white;
            color: #ff6b6b;
            font-size: 0.8rem;
            font-weight: bold;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .manada-v1-cart-icon-img {
            font-style: normal;
            font-size: 1.5rem;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .manada-v1-title {
                font-size: 2.5rem;
            }

            .manada-v1-subtitle {
                font-size: 1.2rem;
            }

            .manada-v1-process-container {
                flex-direction: column;
                align-items: center;
            }

            .manada-v1-process-item {
                max-width: 100%;
                width: 100%;
            }

            .manada-v1-modal-content {
                margin: 20% auto;
                width: 90%;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const manada_v1_container = document.querySelector('.manada-v1-ecommerce-container');
            const manada_v1_cartItems = [];
            const manada_v1_products = {
                'termo-pet-mom': {
                    name: 'Termo In My Pet Mom Era',
                    price: 30000,
                    image: 'https://via.placeholder.com/300x300'
                },
                'tote-pet-mom': {
                    name: 'Tote Bag In My Pet Mom Era',
                    price: 30000,
                    image: 'https://via.placeholder.com/300x300'
                },
                'camiseta-pet-mom': {
                    name: 'Camiseta In My Pet Mom Era',
                    price: 70000,
                    image: 'https://via.placeholder.com/300x300'
                }
            };

            // Add to cart functionality
            const manada_v1_addToCartButtons = manada_v1_container.querySelectorAll('.manada-v1-add-to-cart');
            manada_v1_addToCartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');
                    const product = manada_v1_products[productId];
                    
                    if (product) {
                        manada_v1_cartItems.push({
                            id: productId,
                            name: product.name,
                            price: product.price,
                            quantity: 1
                        });
                        
                        manada_v1_updateCartCount();
                        manada_v1_showNotification('Producto añadido al carrito');
                    }
                });
            });

            // Cart icon click
            const manada_v1_cartIcon = manada_v1_container.querySelector('.manada-v1-cart-icon');
            const manada_v1_cartModal = manada_v1_container.querySelector('#manada-v1-cart-modal');
            manada_v1_cartIcon.addEventListener('click', function() {
                manada_v1_renderCart();
                manada_v1_cartModal.style.display = 'block';
            });

            // Close cart modal
            const manada_v1_closeModal = manada_v1_container.querySelector('.manada-v1-close-modal');
            manada_v1_closeModal.addEventListener('click', function() {
                manada_v1_cartModal.style.display = 'none';
            });

            // Close success modal
            const manada_v1_closeSuccessModal = manada_v1_container.querySelector('.manada-v1-close-success-modal');
            const manada_v1_successModal = manada_v1_container.querySelector('#manada-v1-success-modal');
            manada_v1_closeSuccessModal.addEventListener('click', function() {
                manada_v1_successModal.style.display = 'none';
            });

            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === manada_v1_cartModal) {
                    manada_v1_cartModal.style.display = 'none';
                }
                if (event.target === manada_v1_successModal) {
                    manada_v1_successModal.style.display = 'none';
                }
            });

            // Checkout button
            const manada_v1_checkoutButton = manada_v1_container.querySelector('#manada-v1-checkout-button');
            manada_v1_checkoutButton.addEventListener('click', function() {
                if (manada_v1_cartItems.length > 0) {
                    alert('Redirigiendo al proceso de pago...');
                    // Here you would typically redirect to a checkout page
                } else {
                    alert('Tu carrito está vacío');
                }
            });

            // Form submission
            const manada_v1_personalizedForm = manada_v1_container.querySelector('#manada-v1-personalized-form');
            manada_v1_personalizedForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Here you would typically send the form data to the server
                // For this example, we'll just show a success message
                manada_v1_successModal.style.display = 'block';
                manada_v1_personalizedForm.reset();
            });

            // Update cart count
            function manada_v1_updateCartCount() {
                const manada_v1_cartCount = manada_v1_container.querySelector('.manada-v1-cart-count');
                manada_v1_cartCount.textContent = manada_v1_cartItems.length;
            }

            // Render cart items
            function manada_v1_renderCart() {
                const manada_v1_cartItemsContainer = manada_v1_container.querySelector('#manada-v1-cart-items');
                const manada_v1_cartTotal = manada_v1_container.querySelector('#manada-v1-cart-total');
                
                manada_v1_cartItemsContainer.innerHTML = '';
                
                if (manada_v1_cartItems.length === 0) {
                    manada_v1_cartItemsContainer.innerHTML = '<p>Tu carrito está vacío</p>';
                    manada_v1_cartTotal.textContent = '$ 0';
                    return;
                }
                
                let total = 0;
                
                manada_v1_cartItems.forEach((item, index) => {
                    total += item.price * item.quantity;
                    
                    const itemElement = document.createElement('div');
                    itemElement.className = 'manada-v1-cart-item';
                    itemElement.innerHTML = `
                        <div class="manada-v1-cart-item-info">
                            <div class="manada-v1-cart-item-title">${item.name}</div>
                            <div class="manada-v1-cart-item-price">$ ${item.price.toLocaleString()}</div>
                        </div>
                        <button class="manada-v1-cart-item-remove" data-index="${index}">&times;</button>
                    `;
                    
                    manada_v1_cartItemsContainer.appendChild(itemElement);
                });
                
                manada_v1_cartTotal.textContent = `$ ${total.toLocaleString()}`;
                
                // Add event listeners to remove buttons
                const manada_v1_removeButtons = manada_v1_cartItemsContainer.querySelectorAll('.manada-v1-cart-item-remove');
                manada_v1_removeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const index = parseInt(this.getAttribute('data-index'));
                        manada_v1_cartItems.splice(index, 1);
                        manada_v1_updateCartCount();
                        manada_v1_renderCart();
                    });
                });
            }

            // Show notification
            function manada_v1_showNotification(message) {
                const notification = document.createElement('div');
                notification.className = 'manada-v1-notification';
                notification.textContent = message;
                notification.style.position = 'fixed';
                notification.style.bottom = '100px';
                notification.style.right = '20px';
                notification.style.backgroundColor = '#4CAF50';
                notification.style.color = 'white';
                notification.style.padding = '10px 20px';
                notification.style.borderRadius = '5px';
                notification.style.zIndex = '1000';
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.3s ease';
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.style.opacity = '1';
                }, 10);
                
                setTimeout(() => {
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 300);
                }, 3000);
            }
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('manada_wear_ecommerce', 'manada_wear_ecommerce_shortcode');
?>