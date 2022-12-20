<?php

return [
    'callMeetingCalendarId' => env('CALLMEETINGCALENDARID'),
    'onSiteEvaluationCalendarId' => env('ONSITEEVALUATIONCALENDARID'),
    'contractorNotesId' => env('CONTRACTORNOTESID'),
    'meetingFeedbackId' => env('MEETINGFEEDBACKID'),
    'dispositionId' => env('DISPOSITIONID'),
    'months_before_to_sync' => env('MONTHS_BEFORE_TO_SYNC'),
    'months_after_to_sync' => env('MONTHS_AFTER_TO_SYNC'),
    'token_api' => env('TOKEN_API'),
    'days_before_start_send_email_incomplete' => env('DAYS_BEFORE_START_SEND_EMAIL_INCOMPLETE'),
    'days_before_end_send_email_incomplete' => env('DAYS_BEFORE_END_SEND_EMAIL_INCOMPLETE'),
];
