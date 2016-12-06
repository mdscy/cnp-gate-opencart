<?php
echo $header;
echo $column_left;
echo $column_right;
echo $content_top;
?>
<div style="text-align: center;">
  <h1>Yout transaction was declined</h1>
  <div style="border: 1px solid #DDDDDD; margin-bottom: 20px; width: 350px; margin-left: auto; margin-right: auto;">
  <a href='<?php echo $continue;?>'>Back to Cart</a>
  </div>
</div>

<?php
echo $content_bottom;
echo $footer;




?>
