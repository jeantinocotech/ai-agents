<?php

use App\Support\LogAlertMailRecipients;

test('log alert mail recipients parses comma separated valid emails', function () {
    $emails = LogAlertMailRecipients::parse('ops@example.com, bad, jean@example.org ');

    expect($emails)->toBe(['ops@example.com', 'jean@example.org']);
});

test('log alert mail recipients returns empty for blank input', function () {
    expect(LogAlertMailRecipients::parse(''))->toBe([]);
    expect(LogAlertMailRecipients::parse('  ,  '))->toBe([]);
});
