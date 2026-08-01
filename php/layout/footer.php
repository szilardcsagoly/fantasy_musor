<?php
$included = strtolower(realpath(__FILE__)) != strtolower(realpath($_SERVER['SCRIPT_FILENAME']));
if(!$included) die();

if($mpage != 'admin' && $mpage != 'belépés'){
?>
<footer id="footer" class="py-3 bg-black">
    <p class="text-center text-muted">©2026 - CSZ - media - Fanta műsor</p>
</footer>
<?php } ?>

<!-- JavaScript Fájlok -->
<script src="/js/jquery-3.7.1.min.js" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
<script src="/js/fanta.js?v=5.0" type="text/javascript"></script>



</body>
</html>
