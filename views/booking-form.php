<!--**** BOOKING SECTION ****-->
<section class="booking_container">
    <h2>Book your stay</h2>


    <!-- Display error/success messages INSIDE booking section -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="message error">
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>


    <form class="booking" action="<?= $config['paths']['posts']['create_booking']; ?>" method="post">

        <!-- ask for guestname -->
        <label for="name">Your name:</label>
        <input type="text" id="name" name="name" autocomplete="off"/>

        <!-- ask for guest api-key-->
        <label for="api_key">Api-key (for payment):</label>
        <input type="text" id="api_key" name="api_key" autocomplete="off"/>

        <!-- print features-list -->
        <p class="features-heading"><strong>Features</strong></p>

        <div class="features-list">
            <?php foreach ($grouped_features as $activity => $features_in_group) : ?>

                <div class="feature-category">
                    <h3><?= htmlspecialchars(ucfirst($activity)) ?></h3>

                    <!-- Show features in it's category -->
                    <?php foreach ($features_in_group as $feature) : ?>
                        <div>
                            <input type="checkbox"
                                id="feature-<?= (int)$feature['id'] ?>"
                                name="features[]"
                                value="<?= (int)$feature['id'] ?>"
                                data-price="<?= (int)$feature['price'] ?>"
                                class="feature-checkbox">
                            <label for="feature-<?= (int)$feature['id'] ?>">
                                <?= htmlspecialchars($feature['name']) ?> (<?= (int)$feature['price'] ?> credits)
                            </label>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endforeach ?>
        </div>

        <!-- room dropdown -->
        <label for="room-select">Pick room</label>
        <select name="room_id" id="room-select">
            <?php foreach ($rooms as $room) : ?>
                <option value="<?= (int)$room['id'] ?>" data-price="<?= (int)$room['price'] ?>">
                    <?= htmlspecialchars($room['name']) ?> - <?= htmlspecialchars($room['category']) ?> (<?= (int)$room['price'] ?> credits/night)
                </option>
            <?php endforeach ?>
        </select>

        <!-- Arrival/Departure calendar -->
        <label for="arrival">Arrival</label>
        <input
            type="date"
            name="arrival"
            class="arrival"
            id="arrival"
            value="2026-01-01"
            min="2026-01-01"
            max="2026-01-31"
        />

        <label for="departure">Departure</label>
        <input
            type="date"
            name="departure"
            class="departure"
            id="departure"
            min="2026-01-01"
            max="2026-01-31"
        />

        <!-- display updated price from choices -->
        <p><strong>Total Price: <span id="total-price">0</span> Dharma beers</strong></p>

        <button type="submit" class="btn">BOOK</button>
    </form>
</section>

<script>

    const baseUrl = '<?= $config['base_url'] ?>';
    let currentDiscount = 0;

    async function checkDiscount() {
        const name = document.getElementById('name').value.trim();

        if (!name) {
            currentDiscount = 0;
            updatePrice();
            return;
        }

        try {
                const response = await fetch(`${baseUrl}/app/posts/check-discount.php?name=${encodeURIComponent(name)}`);
            const data = await response.json();
            currentDiscount = data.discount_multiplier;
            updatePrice();

        } catch (error) {
            console.error('Error checking discount:', error);
        }
    }

    async function updatePrice() {
        let total = 0;

        const roomSelect = document.getElementById('room-select');
        const roomPrice = parseInt(roomSelect.options[roomSelect.selectedIndex].dataset.price) || 0;
        const roomId = roomSelect.value;

        const arrival = document.getElementById('arrival').value;
        const departure = document.getElementById('departure').value;

        let nights = 0;
        if (arrival && departure) {
            const arrivalDate = new Date(arrival);
            const departureDate = new Date(departure);
            nights = Math.max(0, (departureDate - arrivalDate) / (1000 * 60 * 60 * 24));
        }

        const selectedFeatures = Array.from(document.querySelectorAll('.feature-checkbox:checked'))
            .map(cb => cb.value);

        //check if package price exist
        let packagePrice = null;
        if (selectedFeatures.length > 0 && nights > 0) {
            try {
                const response = await fetch(`${baseUrl}/app/posts/check-package.php?room_id=${roomId}&features=${selectedFeatures.join(',')}&nights=${nights}`);
                const data = await response.json();
                if (data.package) {
                    packagePrice = data.package_price;
                }
            } catch (error) {
                console.error('Error checking package:', error);
            }
        }

        if (packagePrice !== null) {
            total = Math.round(packagePrice);

        } else {
            total = Math.round(roomPrice * nights);

            document.querySelectorAll('.feature-checkbox:checked').forEach(checkbox => {
                total += parseInt(checkbox.dataset.price) || 0;
            });
        }

        if (currentDiscount > 0) {
            total = Math.round(total * currentDiscount);
        }

        document.getElementById('total-price').textContent = total;
    }

    // name input field
    document.getElementById('name').addEventListener('blur', checkDiscount);

    // eventlisteners
    document.getElementById('room-select').addEventListener('change', updatePrice);
    document.getElementById('arrival').addEventListener('change', updatePrice);
    document.getElementById('departure').addEventListener('change', updatePrice);
    document.querySelectorAll('.feature-checkbox').forEach(cb => {
        cb.addEventListener('change', updatePrice);
    });

    updatePrice();

</script>
