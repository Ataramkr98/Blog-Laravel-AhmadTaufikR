<?php

test('the home page is available before sample content is seeded', function () {
    $this->get('/')->assertOk();
});
