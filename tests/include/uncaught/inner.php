<?php
function fail_inside() {
    throw new RuntimeException("from the included file", 7, new LogicException("the cause"));
}
fail_inside();
