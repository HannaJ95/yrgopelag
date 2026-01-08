<?php

$stars = (int)getIslandStars() ?? 0;

?>


<header class="main_header">
    <img class="header_logo" src="<?= $config['assets']['images']['header_logo'] ?>" alt="">
    <div class="header_stars">

        <?php for ($i=0; $i < $stars; $i++) : ?> 
            <img class="header_star" src="<?= $config['assets']['images']['star'] ?>" alt="star">
        <?php endfor; ?>
    
    </div>
</header>