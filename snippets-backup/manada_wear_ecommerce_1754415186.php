<?php
/*
 * Código generado por DrawCode AI
 * Shortcode: [manada_wear_ecommerce]
 * Fecha: 2025-08-05 17:33:06
 * Timestamp: 1754415186
 * User ID: 1
 */

/**
 * Shortcode para Manada Wear E-commerce
 */
function manada_wear_ecommerce_shortcode() {
    ob_start();
    ?>
    <div class="manada-v1-container">
        <section class="manada-v1-hero">
            <div class="manada-v1-hero-content">
                <h1 class="manada-v1-title">MANADA WEAR</h1>
                <p class="manada-v1-subtitle">Prendas personalizadas con el amor de tu mascota</p>
                <a href="#manada-v1-collection" class="manada-v1-button">Ver Colección</a>
            </div>
        </section>

        <section id="manada-v1-collection" class="manada-v1-collection">
            <h2 class="manada-v1-section-title">NUEVA COLECCIÓN</h2>
            <div class="manada-v1-products">
                <div class="manada-v1-product">
                    <div class="manada-v1-product-image">
                        <img src="https://via.placeholder.com/300x300" alt="Termo In My Pet Mom Era">
                    </div>
                    <h3 class="manada-v1-product-title">Termo In My Pet Mom Era</h3>
                    <p class="manada-v1-product-price">COP $30.000</p>
                    <button class="manada-v1-add-to-cart">Añadir al carrito</button>
                </div>
                <div class="manada-v1-product">
                    <div class="manada-v1-product-image">
                        <img src="https://via.placeholder.com/300x300" alt="Tote Bag In My Pet Mom Era">
                    </div>
                    <h3 class="manada-v1-product-title">Tote Bag In My Pet Mom Era</h3>
                    <p class="manada-v1-product-price">COP $30.000</p>
                    <button class="manada-v1-add-to-cart">Añadir al carrito</button>
                </div>
                <div class="manada-v1-product">
                    <div class="manada-v1-product-image">
                        <img src="https://via.placeholder.com/300x300" alt="Camiseta In My Pet Mom Era">
                    </div>
                    <h3 class="manada-v1-product-title">Camiseta In My Pet Mom Era</h3>
                    <p class="manada-v1-product-price">COP $70.000</p>
                    <button class="manada-v1-add-to-cart">Añadir al carrito</button>
                </div>
            </div>
        </section>

        <section class="manada-v1-process">
            <h2 class="manada-v1-section-title">TU PRENDA PERSONALIZADA FAVORITA EN 4 PASOS</h2>
            <div class="manada-v1-steps">
                <div class="manada-v1-step">
                    <div class="manada-v1-step-number">1</div>
                    <h3 class="manada-v1-step-title">Compra y fotos</h3>
                    <p class="manada-v1-step-description">Elige tu prenda favorita en nuestra web y sube las fotos de tu peludo. Así podremos crear una ilustración perfecta.</p>
                    <p class="manada-v1-step-time">⏱️ Tiempo: 1 día</p>
                </div>
                <div class="manada-v1-step">
                    <div class="manada-v1-step-number">2</div>
                    <h3 class="manada-v1-step-title">Ilustración personalizada</h3>
                    <p class="manada-v1-step-description">Recibirás una ilustración única por WhatsApp. Puedes pedir ajustes hasta que estés feliz.</p>
                    <p class="manada-v1-step-time">⏱️ Tiempo: 2-3 días</p>
                </div>
                <div class="manada-v1-step">
                    <div class="manada-v1-step-number">3</div>
                    <h3 class="manada-v1-step-title">Bordado artesanal</h3>
                    <p class="manada-v1-step-description">Nuestra abuela perruna bordará tu prenda con amor y dedicación.</p>
                    <p class="manada-v1-step-time">⏱️ Tiempo: 5-6 días</p>
                </div>
                <div class="manada-v1-step">
                    <div class="manada-v1-step-number">4</div>
                    <h3 class="manada-v1-step-title">Envío con cariño</h3>
                    <p class="manada-v1-step-description">Empacamos tu prenda y te enviamos la guía de seguimiento.</p>
                    <p class="manada-v1-step-time">📦 Tiempo total: aprox. 10 días</p>
                </div>
            </div>
        </section>

        <section class="manada-v1-custom-order">
            <h2 class="manada-v1-section-title">PERSONALIZA TU PRENDA</h2>
            <form id="manada-v1-custom-form" class="manada-v1-form">
                <div class="manada-v1-form-group">
                    <label for="manada-v1-name" class="manada-v1-label">Nombre</label>
                    <input type="text" id="manada-v1-name" class="manada-v1-input" required>
                </div>
                <div class="manada-v1-form-group">
                    <label for="manada-v1-email" class="manada-v1-label">Email</label>
                    <input type="email" id="manada-v1-email" class="manada-v1-input" required>
                </div>
                <div class="manada-v1-form-group">
                    <label for="manada-v1-phone" class="manada-v1-label">Teléfono</label>
                    <input type="tel" id="manada-v1-phone" class="manada-v1-input" required>
                </div>
                <div class="manada-v1-form-group">
                    <label for="manada-v1-product" class="manada-v1-label">Producto</label>
                    <select id="manada-v1-product" class="manada-v1-select" required>
                        <option value="">Selecciona un producto</option>
                        <option value="termo">Termo In My Pet Mom Era</option>
                        <option value="tote">Tote Bag In My Pet Mom Era</option>
                        <option value="camiseta">Camiseta In My Pet Mom Era</option>
                    </select>
                </div>
                <div class="manada-v1-form-group">
                    <label for="manada-v1-pet-photo" class="manada-v1-label">Foto de tu mascota</label>
                    <input type="file" id="manada-v1-pet-photo" class="manada-v1-file-input" accept="image/*" required>
                </div>
                <div class="manada-v1-form-group">
                    <label for="manada-v1-message" class="manada-v1-label">Mensaje adicional</label>
                    <textarea id="manada-v1-message" class="manada-v1-textarea" rows="4"></textarea>
                </div>
                <button type="submit" class="manada-v1-submit-button">Enviar pedido</button>
            </form>
        </section>
    </div>

    <style>
        .manada-v1-container {
            font-family: 'Montserrat', sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            color: #333;
        }

        .manada-v1-hero {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://via.placeholder.com/1200x600');
            background-size: cover;
            background-position: center;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin-bottom: 60px;
            border-radius: 10px;
        }

        .manada-v1-hero-content {
            color: white;
            padding: 20px;
        }

        .manada-v1-title {
            font-size: 3.5rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .manada-v1-subtitle {
            font-size: 1.5rem;
            margin-bottom: 30px;
        }

        .manada-v1-button {
            display: inline-block;
            background-color: #ff6b6b;
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .manada-v1-button:hover {
            background-color: #ff5252;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .manada-v1-section-title {
            text-align: center;
            font-size: 2.2rem;
            margin-bottom: 40px;
            position: relative;
            padding-bottom: 15px;
            color: #333;
        }

        .manada-v1-section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: #ff6b6b;
        }

        .manada-v1-collection {
            margin-bottom: 80px;
        }

        .manada-v1-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .manada-v1-product {
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            text-align: center;
            padding-bottom: 20px;
        }

        .manada-v1-product:hover {
            transform: translateY(-10px);
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

        .manada-v1-product:hover .manada-v1-product-image img {
            transform: scale(1.1);
        }

        .manada-v1-product-title {
            margin: 15px 0 10px;
            font-size: 1.2rem;
            padding: 0 15px;
        }

        .manada-v1-product-price {
            color: #ff6b6b;
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 15px;
        }

        .manada-v1-add-to-cart {
            background-color: #ff6b6b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .manada-v1-add-to-cart:hover {
            background-color: #ff5252;
        }

        .manada-v1-process {
            margin-bottom: 80px;
            background-color: #f9f9f9;
            padding: 60px 30px;
            border-radius: 10px;
        }

        .manada-v1-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .manada-v1-step {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            position: relative;
        }

        .manada-v1-step-number {
            background-color: #ff6b6b;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            margin: 0 auto 20px;
        }

        .manada-v1-step-title {
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .manada-v1-step-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .manada-v1-step-time {
            font-weight: 600;
            color: #ff6b6b;
        }

        .manada-v1-custom-order {
            margin-bottom: 80px;
        }

        .manada-v1-form {
            max-width: 700px;
            margin: 0 auto;
            background-color: #f9f9f9;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .manada-v1-form-group {
            margin-bottom: 20px;
        }

        .manada-v1-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .manada-v1-input,
        .manada-v1-select,
        .manada-v1-textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .manada-v1-input:focus,
        .manada-v1-select:focus,
        .manada-v1-textarea:focus {
            border-color: #ff6b6b;
            outline: none;
        }

        .manada-v1-file-input {
            padding: 10px 0;
        }

        .manada-v1-submit-button {
            background-color: #ff6b6b;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: block;
            width: 100%;
            font-size: 1.1rem;
            margin-top: 10px;
        }

        .manada-v1-submit-button:hover {
            background-color: #ff5252;
        }

        @media (max-width: 768px) {
            .manada-v1-title {
                font-size: 2.5rem;
            }
            
            .manada-v1-subtitle {
                font-size: 1.2rem;
            }
            
            .manada-v1-section-title {
                font-size: 1.8rem;
            }
            
            .manada-v1-hero {
                height: 400px;
            }
            
            .manada-v1-form {
                padding: 30px 20px;
            }
        }

        @media (max-width: 480px) {
            .manada-v1-title {
                font-size: 2rem;
            }
            
            .manada-v1-hero {
                height: 350px;
            }
            
            .manada-v1-products {
                grid-template-columns: 1fr;
            }
            
            .manada-v1-section-title {
                font-size: 1.5rem;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const manada_v1_container = document.querySelector('.manada-v1-container');
            
            // Form submission
            const manada_v1_form = manada_v1_container.querySelector('#manada-v1-custom-form');
            if (manada_v1_form) {
                manada_v1_form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Get form values
                    const manada_v1_name = manada_v1_container.querySelector('#manada-v1-name').value;
                    const manada_v1_email = manada_v1_container.querySelector('#manada-v1-email').value;
                    const manada_v1_phone = manada_v1_container.querySelector('#manada-v1-phone').value;
                    const manada_v1_product = manada_v1_container.querySelector('#manada-v1-product').value;
                    
                    // Validate form
                    if (!manada_v1_name || !manada_v1_email || !manada_v1_phone || !manada_v1_product) {
                        alert('Por favor completa todos los campos requeridos');
                        return;
                    }
                    
                    // Here you would typically send the data to your server
                    alert('¡Gracias por tu pedido! Te contactaremos pronto para confirmar los detalles.');
                    manada_v1_form.reset();
                });
            }
            
            // Add to cart buttons
            const manada_v1_addToCartButtons = manada_v1_container.querySelectorAll('.manada-v1-add-to-cart');
            manada_v1_addToCartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const manada_v1_product = this.closest('.manada-v1-product');
                    const manada_v1_productTitle = manada_v1_product.querySelector('.manada-v1-product-title').textContent;
                    const manada_v1_productPrice = manada_v1_product.querySelector('.manada-v1-product-price').textContent;
                    
                    alert(`Añadido al carrito: ${manada_v1_productTitle} - ${manada_v1_productPrice}`);
                    
                    // Here you would typically update your cart
                });
            });
            
            // Smooth scrolling for anchor links
            const manada_v1_anchorLinks = manada_v1_container.querySelectorAll('a[href^="#"]');
            manada_v1_anchorLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const manada_v1_targetId = this.getAttribute('href');
                    const manada_v1_targetElement = manada_v1_container.querySelector(manada_v1_targetId);
                    
                    if (manada_v1_targetElement) {
                        window.scrollTo({
                            top: manada_v1_targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('manada_wear_ecommerce', 'manada_wear_ecommerce_shortcode');
?>