<!DOCTYPE html>
<html lang="fa">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بسته‌بندی محصولات با Three.js</title>
    <!-- بارگذاری کتابخانه‌های Three.js و کنترل‌ها -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three/examples/js/controls/OrbitControls.js"></script>
    <link rel="stylesheet" href="./baresi bak.css">
</head>

<body>
    <div id="canvas-container"></div>

    <!-- دکمه باز/بسته کردن سایدبار -->
    <button class="toggle-sidebar" onclick="toggleSidebar()">☰</button>
    <div id="product-info"></div>

    <!-- سایدبار -->
    <div class="sidebar" id="sidebar">
        <h3>ابعاد جعبه و محصولات</h3>
        <label for="carton-size">اندازه جعبه:</label>
        <select id="carton-size" onchange="updateCartonInfo()">
            <option value="15x10x10">سایز ۱: ابعاد ۱۵۰ × ۱۰۰ × ۱۰۰ میلیمتر</option>
            <option value="20x15x10">سایز ۲: ابعاد ۲۰۰ × ۱۵۰ × ۱۰۰ میلیمتر</option>
            <option value="20x20x15">سایز ۳: ابعاد ۲۰۰ × ۲۰۰ × ۱۵۰ میلیمتر</option>
            <option value="30x20x20">سایز ۴: ابعاد ۳۰۰ × ۲۰۰ × ۲۰۰ میلیمتر</option>
            <option value="35x25x20">سایز ۵: ابعاد ۳۵۰ × ۲۵۰ × ۲۰۰ میلیمتر</option>
            <option value="45x25x20">سایز ۶: ابعاد ۴۵۰ × ۲۵۰ × ۲۰۰ میلیمتر</option>
            <option value="40x30x25">سایز ۷: ابعاد ۴۰۰ × ۳۰۰ × ۲۵۰ میلیمتر</option>
            <option value="45x40x30">سایز ۸: ابعاد ۴۵۰ × ۴۰۰ × ۳۰۰ میلیمتر</option>
            <option value="55x45x35">سایز ۹: ابعاد ۵۵۰ × ۴۵۰ × ۳۵۰ میلیمتر</option>
            <option value="39x51x34">جعبه شماره 10 (39x51x34)</option>
        </select>
        <div id="carton-dimensions" style="margin-bottom: 15px;"></div>
        <label for="product-count">تعداد محصولات:</label>
        <input type="number" id="product-count" value="1" min="1" max="100" onchange="updateProductInputs(); render3D();">
        <div id="product-inputs"></div>
        <button onclick="toggleSidebar()">بستن</button>
        <button class="find-button" onclick="findSmallestCarton()">پیدا کردن کوچک‌ترین جعبه</button>
    </div>

    <script>
        // لیست جعبه‌ها با ابعادشان
        const cartons = [
            { number: 1, width: 15, length: 10, height: 10 },
            { number: 2, width: 20, length: 15, height: 10 },
            { number: 3, width: 20, length: 20, height: 15 },
            { number: 4, width: 30, length: 20, height: 20 },
            { number: 5, width: 35, length: 25, height: 20 },
            { number: 6, width: 45, length: 25, height: 20 },
            { number: 7, width: 40, length: 30, height: 25 },
            { number: 8, width: 45, length: 40, height: 30 },
            { number: 9, width: 55, length: 45, height: 35 },
            { number: 10, width: 39, length: 51, height: 34 }
        ];

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        // انتخاب المان کانتینر بوم
        const container = document.getElementById('canvas-container');

        // ایجاد صحنه Three.js
        const scene = new THREE.Scene();

        // ایجاد دوربین پرسپکتیو
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);

        // ایجاد رندرر با فعال‌سازی آنتی‌آلیاسینگ
        const renderer = new THREE.WebGLRenderer({
            antialias: true
        });
        const raycaster = new THREE.Raycaster();
        const mouse = new THREE.Vector2();
        renderer.setSize(window.innerWidth, window.innerHeight);
        container.appendChild(renderer.domElement);

        // افزودن کنترل‌های OrbitControls
        const orbitControls = new THREE.OrbitControls(camera, renderer.domElement);
        orbitControls.enableDamping = true;
        orbitControls.dampingFactor = 0.05;
        orbitControls.minDistance = 50;
        orbitControls.maxDistance = 800;

        // تنظیم موقعیت اولیه دوربین
        camera.position.set(0, 100, 400);

        // گروه برای جعبه و محصولات
        const cartonGroup = new THREE.Group();
        scene.add(cartonGroup);

        // آرایه برای نگهداری محصولات
        let products = [];

        // ابعاد اولیه جعبه
        let cartonDimensions = {
            width: 10,
            length: 14,
            height: 10
        };

        /**
         * به‌روزرسانی اطلاعات جعبه بر اساس انتخاب کاربر
         */
        function updateCartonInfo() {
            const selectedSize = document.getElementById('carton-size').value;
            const [width, length, height] = selectedSize.split('x').map(Number);
            cartonDimensions = {
                width,
                length,
                height
            };
            document.getElementById('carton-dimensions').innerText = `${width}cm x ${length}cm x ${height}cm  <=  اندازه جعبه`;
            render3D(); // به‌روزرسانی بلادرنگ
        }

        /**
         * به‌روزرسانی ورودی‌های محصولات بر اساس تعداد انتخاب شده
         */
        function updateProductInputs() {
            const productCount = parseInt(document.getElementById('product-count').value);
            const productInputs = document.getElementById('product-inputs');
            productInputs.innerHTML = '';
            for (let i = 0; i < productCount; i++) {
                productInputs.innerHTML += `
                    <label>محصول ${i + 1}:</label>
                    <input type="number" id="product${i + 1}-width" placeholder="عرض (سانتی‌متر)" value="1" min="1" oninput="render3D()">
                    <input type="number" id="product${i + 1}-length" placeholder="طول (سانتی‌متر)" value="1" min="1" oninput="render3D()">
                    <input type="number" id="product${i + 1}-height" placeholder="ارتفاع (سانتی‌متر)" value="1" min="1" oninput="render3D()">
                `;
            }
            render3D(); // رندر پس از به‌روزرسانی ورودی‌ها
        }

        window.addEventListener('mousemove', onMouseMove);

        function onMouseMove(event) {
            // محاسبه موقعیت ماوس در فضای نرمال
            mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
            mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;

            // بررسی برخورد با اشیا
            raycaster.setFromCamera(mouse, camera);
            const intersects = raycaster.intersectObjects(products);

            // بازنشانی تمام محصولات به حالت عادی
            products.forEach(product => {
                product.material.emissive.set(0x000000); // تنظیم نوردهی به مقدار عادی
            });

            // اگر ماوس روی محصول قرار گرفت
            if (intersects.length > 0) {
                const hoveredProduct = intersects[0].object;
                hoveredProduct.material.emissive.set(0xff0000); // تغییر رنگ برای نمایش هاور

                // شماره محصول را در کنسول یا صفحه نمایش دهید
                const productIndex = products.indexOf(hoveredProduct);
                console.log(`محصول شماره: ${productIndex + 1}`); // شماره محصول
                displayProductInfo(productIndex + 1); // نمایش در صفحه
            }
        }

        function displayProductInfo(productNumber) {
            const infoBox = document.getElementById('product-info');
            infoBox.style.display = 'block';
            infoBox.innerText = `محصول شماره: ${productNumber}`;
        }

        // مخفی کردن اطلاعات هنگام خروج ماوس
        window.addEventListener('mouseout', () => {
            const infoBox = document.getElementById('product-info');
            infoBox.style.display = 'none';
        });

        /**
         * رندر مجدد صحنه سه‌بعدی
         */
        function render3D() {
            // پاکسازی گروه جعبه و محصولات
            cartonGroup.clear();
            products = [];

            const scaleFactor = 6; // فاکتور مقیاس برای بهتر نمایش دادن ابعاد

            const scaledCartonDimensions = {
                width: cartonDimensions.width * scaleFactor,
                height: cartonDimensions.height * scaleFactor,
                length: cartonDimensions.length * scaleFactor
            };

            // ایجاد جعبه
            const cartonMaterial = new THREE.MeshBasicMaterial({
                color: 0xcccccc,
                wireframe: true
            });
            const cartonGeometry = new THREE.BoxGeometry(
                scaledCartonDimensions.width,
                scaledCartonDimensions.height,
                scaledCartonDimensions.length
            );
            const cartonMesh = new THREE.Mesh(cartonGeometry, cartonMaterial);
            cartonGroup.add(cartonMesh);

            // دریافت تعداد محصولات
            const productCount = parseInt(document.getElementById('product-count').value);
            const placedProducts = [];
            let allPlaced = true;

            // قرار دادن هر محصول در جعبه
            for (let i = 0; i < productCount; i++) {
                const width = parseInt(document.getElementById(`product${i + 1}-width`).value) * scaleFactor;
                const length = parseInt(document.getElementById(`product${i + 1}-length`).value) * scaleFactor;
                const height = parseInt(document.getElementById(`product${i + 1}-height`).value) * scaleFactor;

                const product = {
                    width,
                    height,
                    length
                };

                // دریافت تمامی چرخش‌های ممکن که جا می‌گیرند
                const fittingPermutations = canFitInBox(product, scaledCartonDimensions);

                let placed = false;

                // تلاش برای قرار دادن محصول با هر چرخش ممکن
                for (const perm of fittingPermutations) {
                    const [rotatedWidth, rotatedHeight, rotatedLength] = perm;

                    // تلاش برای قرار دادن محصول در موقعیت‌های مختلف با گام کوچک‌تر
                    for (let y = -scaledCartonDimensions.height / 2; y <= scaledCartonDimensions.height / 2 - rotatedHeight; y += 1) {
                        for (let z = -scaledCartonDimensions.length / 2; z <= scaledCartonDimensions.length / 2 - rotatedLength; z += 1) {
                            for (let x = -scaledCartonDimensions.width / 2; x <= scaledCartonDimensions.width / 2 - rotatedWidth; x += 1) {
                                if (!isColliding(x, y, z, rotatedWidth, rotatedHeight, rotatedLength, placedProducts)) {
                                    // قرار دادن محصول
                                    const productGeometry = new THREE.BoxGeometry(rotatedWidth, rotatedHeight, rotatedLength);
                                    const productMaterial = new THREE.MeshPhongMaterial({
                                        color: new THREE.Color(Math.random(), Math.random(), Math.random())
                                    });
                                    const productMesh = new THREE.Mesh(productGeometry, productMaterial);

                                    productMesh.position.set(
                                        x + rotatedWidth / 2,
                                        y + rotatedHeight / 2,
                                        z + rotatedLength / 2
                                    );

                                    cartonGroup.add(productMesh);
                                    placedProducts.push({
                                        x,
                                        y,
                                        z,
                                        width: rotatedWidth,
                                        height: rotatedHeight,
                                        length: rotatedLength
                                    });
                                    products.push(productMesh);
                                    placed = true;
                                    break;
                                }
                            }
                            if (placed) break;
                        }
                        if (placed) break;
                    }

                    if (placed) break; // محصول با این چرخش قرار گرفت
                }

                if (!placed) {
                    allPlaced = false;
                    console.error(`محصول شماره ${i + 1} جا نشد.`);
                }
            }

            // تغییر رنگ پس‌زمینه بر اساس موفقیت در قرار دادن تمام محصولات
            if (!allPlaced) {
                renderer.setClearColor(0xffcccc); // قرمز روشن برای نشان دادن اشکال
            } else {
                renderer.setClearColor(0xf4f4f9); // رنگ پس‌زمینه اصلی
            }

            orbitControls.update();

            animate();
        }

        /**
         * بررسی تداخل محصولات
         * @param {number} x - مختصات x
         * @param {number} y - مختصات y
         * @param {number} z - مختصات z
         * @param {number} width - عرض محصول
         * @param {number} height - ارتفاع محصول
         * @param {number} length - طول محصول
         * @param {Array} placedProducts - لیست محصولات قرار داده شده
         * @returns {boolean} - آیا تداخل دارد یا خیر
         */
        function isColliding(x, y, z, width, height, length, placedProducts) {
            for (const product of placedProducts) {
                if (
                    x < product.x + product.width && x + width > product.x &&
                    y < product.y + product.height && y + height > product.y &&
                    z < product.z + product.length && z + length > product.z
                ) {
                    return true; // تداخل وجود دارد
                }
            }
            return false; // تداخلی وجود ندارد
        }

        /**
         * افزودن نور به صحنه برای بهتر نمایش دادن محصولات
         */
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
        scene.add(ambientLight);

        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.6);
        directionalLight.position.set(100, 100, 100);
        scene.add(directionalLight);

        /**
         * تابع انیمیشن برای رندر مجدد صحنه
         */
        function animate() {
            requestAnimationFrame(animate);
            orbitControls.update();
            renderer.render(scene, camera);
        }

        /**
         * به‌روزرسانی اندازه رندرر و دوربین در تغییر اندازه پنجره
         */
        window.addEventListener('resize', () => {
            const width = window.innerWidth;
            const height = window.innerHeight;
            renderer.setSize(width, height);
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
        });

        /**
         * بررسی اینکه آیا محصول می‌تواند در جعبه جا بگیرد و بازگرداندن تمامی چرخش‌های ممکن که جا می‌گیرند
         * @param {Object} product - ابعاد محصول
         * @param {Object} box - ابعاد جعبه
         * @returns {Array} - لیستی از چرخش‌های ممکن که جا می‌گیرند
         */
        function canFitInBox(product, box) {
            const productDimensions = [product.width, product.height, product.length];
            const boxDimensions = [box.width, box.height, box.length];

            const permutations = permute(productDimensions);
            const fittingPermutations = [];

            for (const perm of permutations) {
                if (
                    perm[0] <= boxDimensions[0] &&
                    perm[1] <= boxDimensions[1] &&
                    perm[2] <= boxDimensions[2]
                ) {
                    fittingPermutations.push(perm);
                }
            }

            return fittingPermutations;
        }

        /**
         * تولید تمامی ترتیب‌های ممکن برای یک آرایه
         * @param {Array} arr - آرایه ورودی
         * @returns {Array} - لیستی از تمامی ترتیب‌های ممکن
         */
        function permute(arr) {
            if (arr.length === 0) return [[]];
            const result = [];
            for (let i = 0; i < arr.length; i++) {
                const rest = permute(arr.slice(0, i).concat(arr.slice(i + 1)));
                rest.forEach(r => result.push([arr[i], ...r]));
            }
            return result;
        }

        /**
         * پیدا کردن کوچک‌ترین جعبه‌ای که می‌تواند همه محصولات را در خود جای دهد
         */
        function findSmallestCarton() {
            // دریافت اطلاعات محصولات
            const productCount = parseInt(document.getElementById('product-count').value);
            const productsList = [];
            for (let i = 0; i < productCount; i++) {
                const width = parseInt(document.getElementById(`product${i + 1}-width`).value);
                const length = parseInt(document.getElementById(`product${i + 1}-length`).value);
                const height = parseInt(document.getElementById(`product${i + 1}-height`).value);
                productsList.push({ width, length, height });
            }

            // مرتب‌سازی محصولات از بزرگ‌ترین به کوچک‌ترین بر اساس حجم
            productsList.sort((a, b) => (b.width * b.length * b.height) - (a.width * a.length * a.height));

            // مرتب‌سازی جعبه‌ها از کوچک‌ترین به بزرگ‌ترین بر اساس حجم
            const sortedCartons = cartons.slice().sort((a, b) => {
                const volumeA = a.width * a.length * a.height;
                const volumeB = b.width * b.length * b.height;
                return volumeA - volumeB;
            });

            // بررسی هر جعبه برای یافتن اولین جعبه‌ای که می‌تواند همه محصولات را در خود جای دهد
            for (const carton of sortedCartons) {
                const scaleFactor = 6; // همان scaleFactor استفاده شده در render3D
                const scaledCartonDimensions = {
                    width: carton.width * scaleFactor,
                    height: carton.height * scaleFactor,
                    length: carton.length * scaleFactor
                };

                const placedProducts = [];
                let allPlaced = true;

                for (const product of productsList) {
                    const scaledProduct = {
                        width: product.width * scaleFactor,
                        height: product.height * scaleFactor,
                        length: product.length * scaleFactor
                    };

                    const fittingPermutations = canFitInBox(scaledProduct, scaledCartonDimensions);

                    let placed = false;

                    for (const perm of fittingPermutations) {
                        const [rotatedWidth, rotatedHeight, rotatedLength] = perm;

                        // تلاش برای قرار دادن محصول در موقعیت‌های مختلف با گام کوچک‌تر
                        for (let y = -scaledCartonDimensions.height / 2; y <= scaledCartonDimensions.height / 2 - rotatedHeight; y += 1) {
                            for (let z = -scaledCartonDimensions.length / 2; z <= scaledCartonDimensions.length / 2 - rotatedLength; z += 1) {
                                for (let x = -scaledCartonDimensions.width / 2; x <= scaledCartonDimensions.width / 2 - rotatedWidth; x += 1) {
                                    if (!isColliding(x, y, z, rotatedWidth, rotatedHeight, rotatedLength, placedProducts)) {
                                        // قرار دادن محصول در محاسبات (بدون افزودن به صحنه)
                                        placedProducts.push({
                                            x,
                                            y,
                                            z,
                                            width: rotatedWidth,
                                            height: rotatedHeight,
                                            length: rotatedLength
                                        });
                                        placed = true;
                                        break;
                                    }
                                }
                                if (placed) break;
                            }
                            if (placed) break;
                        }

                        if (placed) break; // محصول با این چرخش قرار گرفت
                    }

                    if (!placed) {
                        allPlaced = false;
                        console.log(`جعبه شماره ${carton.number} نمی‌تواند محصولی با ابعاد ${product.width}x${product.length}x${product.height} را جا دهد.`);
                        break; // این جعبه نمی‌تواند این محصول را جا دهد، ادامه به جعبه بعدی
                    }
                }

                if (allPlaced) {
                    alert(`این محصولات در کوچک‌ترین جعبه‌ای که جا می‌شوند، جعبه شماره ${carton.number} هست.`);
                    return;
                }
            }

            // اگر هیچ جعبه‌ای مناسب نبود
            alert('هیچ جعبه‌ای نمی‌تواند همه محصولات را در خود جای دهد.');
        }

        // شروع برنامه با به‌روزرسانی ورودی‌ها و اطلاعات اولیه
        animate();
        updateProductInputs();
        updateCartonInfo();
    </script>
</body>

</html>
