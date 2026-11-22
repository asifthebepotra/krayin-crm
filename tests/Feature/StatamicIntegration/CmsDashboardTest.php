<?php

it('shows the CMS dashboard to an authenticated administrator', function () {
    $response = test()
        ->actingAs(getDefaultAdmin())
        ->get(route('admin.cms.index'))
        ->assertOk();

    $response->assertSee('Content Management System');
    $response->assertSee('Statamic CMS');
});

it('requires authentication to open the CMS dashboard', function () {
    test()
        ->get(route('admin.cms.index'))
        ->assertRedirect(route('admin.session.create'));
});