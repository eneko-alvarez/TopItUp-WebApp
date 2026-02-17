<?php
/**
 * English Translations
 * Language: en (English)
 */

return [
    // Authentication & Login
    'auth' => [
        'login' => [
            'title' => 'Sign In',
            'username' => 'Username',
            'username_placeholder' => 'Enter your username',
            'password' => 'Password',
            'password_placeholder' => 'Enter your password',
            'submit' => 'Sign In',
        ],
        'register' => [
            'title' => 'Sign Up',
            'username' => 'Username',
            'username_placeholder' => 'Choose a username',
            'email' => 'Email',
            'email_placeholder' => 'Enter your email',
            'password' => 'Password',
            'password_placeholder' => 'Create a password',
            'confirm_password' => 'Confirm Password',
            'confirm_password_placeholder' => 'Confirm your password',
            'submit' => 'Create Account',
            'username_check' => [
                'checking' => 'Checking...',
                'available' => 'Username available',
                'taken' => 'Username already taken',
                'min_length' => 'At least 3 characters required',
            ],
        ],
        'errors' => [
            'passwords_mismatch' => 'Passwords do not match.',
            'password_min_length' => 'Password must be at least 8 characters.',
            'invalid_credentials' => 'Invalid credentials.',
            'username_exists' => 'Username or email already exists.',
        ],
        'welcome_text' => 'Track what matters to you',
    ],

    // Landing Page (Installation)
    'landing' => [
        'subtitle' => 'Track what matters.<br>Right from your pocket.',
        'install_text' => 'Easier installation than from the<br>App Store or Google Play Store',
        'ios_button' => 'For iOS',
        'android_button' => 'For Android',
        'ios_instructions' => [
            'title' => 'iOS Install',
            'step1' => 'Tap the <strong>Share</strong> button.',
            'step2' => 'Scroll down (or tap \'more\') and tap <strong>"Add to Home Screen"</strong>.',
            'step3' => 'Tap <strong>Add</strong> in the top right.',
            'success' => 'You\'ve successfully installed TopItUp! Open it and start using it!',
        ],
        'android_instructions' => [
            'title' => 'Android Install',
            'step1' => 'Tap the <strong>Menu</strong> icon (three dots).',
            'step2' => 'Tap <strong>"Install App"</strong> or <strong>"Add to Home screen"</strong>.',
            'step3' => 'Follow the prompt to install.',
            'success' => 'You\'ve successfully installed TopItUp! Open it and start using it!',
        ],
    ],

    // Navigation
    'nav' => [
        'dashboard' => 'Dashboard',
        'leaderboard' => 'Leaderboard',
        'settings' => 'Settings',
        'logout' => 'Logout',
    ],

    // Dashboard
    'dashboard' => [
        'title' => 'TopItUp Dashboard',
        'subtitle' => '2026 WRAP :)',
        'empty' => [
            'title' => 'You have no groups or counters',
            'message' => 'Go to Settings to create your first group or counter.',
        ],
        'history' => [
            'title' => 'History (Last 10)',
            'empty' => 'No recent activity.',
        ],
        'no_counters_assigned' => 'No counters assigned',
    ],

    // Settings
    'settings' => [
        'create_counter' => [
            'title' => 'Create New Counter',
            'name' => 'Counter Name',
            'name_placeholder' => 'E.g., Beers, Workouts',
            'color' => 'Color',
            'type' => 'Counter Type',
            'type_classic' => 'Classic (+1)',
            'type_custom' => 'Custom (Variable)',
            'submit' => 'Create Counter',
        ],
        'create_group' => [
            'title' => 'Create New Group',
            'name' => 'Group Name',
            'name_placeholder' => 'E.g., Drinks',
            'color' => 'Color',
            'submit' => 'Create Group',
        ],
        'my_groups' => [
            'title' => 'My Groups',
            'empty' => 'No groups yet',
            'no_counters' => 'No counters',
            'add_counters' => 'Add counters',
            'total' => 'Total',
            'delete' => 'Delete',
        ],
        'my_counters' => [
            'title' => 'My Counters',
            'empty' => 'No counters yet',
            'count' => 'Count',
            'view' => 'View',
            'unassigned' => 'Unassigned',
        ],
        'errors' => [
            'counter_exists' => 'A counter with that name already exists.',
            'group_exists' => 'A group with that name already exists.',
            'create_failed' => 'Failed to create. Please try again.',
        ],
        'success' => [
            'counter_created' => 'Counter created successfully!',
            'group_created' => 'Group created successfully!',
            'counter_deleted' => 'Counter deleted successfully!',
            'group_deleted' => 'Group deleted successfully!',
        ],
    ],

    // Increment Page
    'increment' => [
        'add_one' => '+1',
        'history' => 'History',
        'empty_history' => 'No logs yet',
        'delete_log' => 'Delete',
        'confirm_delete_log' => 'Delete this log entry?',
        'custom_modal_title' => 'Increment',
        'custom_input_placeholder' => 'Enter number',
        'custom_invalid_value' => 'Please enter a valid number between 1 and 9999',
    ],

    // Leaderboard
    'leaderboard' => [
        'join' => [
            'title' => 'Join a Leaderboard',
            'code' => 'Invitation Code',
            'code_placeholder' => 'Enter the code',
            'submit' => 'Join',
        ],
        'create' => [
            'title' => 'Create New Leaderboard',
            'name' => 'Leaderboard Name',
            'name_placeholder' => 'E.g., Beer Challenge',
            'track_type' => 'Track',
            'track_counter' => 'Single Counter',
            'track_group' => 'All Group Counters',
            'select_counter' => 'Select Counter',
            'select_group' => 'Select Group',
            'submit' => 'Create Leaderboard',
        ],
        'my_leaderboards' => 'My Leaderboards',
        'empty' => 'No leaderboards yet',
        'members' => 'members',
        'view' => [
            'rankings' => 'Rankings',
            'rank' => 'Rank',
            'member' => 'Member',
            'count' => 'Count',
            'settings' => 'Settings',
            'last_updated' => 'Last updated',
            'empty' => 'No members yet. Share the invite code to get people to join!',
        ],
        'settings' => [
            'title' => 'Leaderboard Settings',
            'members' => 'Members',
            'no_members' => 'No members yet',
            'invite_code' => 'Invite Code',
            'invitation_code' => 'Invitation Code',
            'copy' => 'Copy',
            'copied' => 'Copied!',
            'remove_member' => 'Remove',
            'leave_leaderboard' => 'Leave Leaderboard',
            'delete_leaderboard' => 'Delete Leaderboard',
            'confirm_leave' => 'Are you sure you want to leave this leaderboard?',
            'confirm_delete' => 'Are you sure? This will delete the leaderboard for everyone.',
            'danger_zone' => 'Danger Zone',
            'leave' => 'Leave Leaderboard',
            'leave_message' => 'Leaving the leaderboard will remove you from the rankings. You\'ll need a new invite code to rejoin.',
            'change_tracking' => 'Change What You Track',
            'single_counter' => 'Single Counter',
            'counter_group' => 'Counter Group',
            'select_counter' => 'Select Counter',
            'select_group' => 'Select Group',
            'timespan' => [
                'title' => 'Time Span Filter',
                'description' => 'Only logs within this date range will count towards rankings.',
                'start' => 'Start Date',
                'end' => 'End Date',
                'submit' => 'Update Time Span',
            ],
        ],
        'errors' => [
            'invalid_code' => 'Invalid invitation code.',
            'already_member' => 'You are already a member.',
            'create_failed' => 'Failed to create leaderboard.',
            'select_item' => 'Please select a counter or group.',
        ],
        'success' => [
            'joined' => 'Successfully joined the leaderboard!',
            'created' => 'Leaderboard created successfully!',
            'left' => 'You have left the leaderboard.',
            'deleted' => 'Leaderboard deleted successfully.',
        ],
    ],

    // Common
    'common' => [
        'page_not_found' => 'Page not found.',
        'error' => 'Error',
        'success' => 'Success',
        'delete' => 'Delete',
        'cancel' => 'Cancel',
        'confirm' => 'Confirm',
        'save' => 'Save',
        'edit' => 'Edit',
        'close' => 'Close',
        'loading' => 'Loading...',
        'user' => 'User',
        'copy' => 'Copy',
        'track' => 'Track',
        'select' => 'Select',
    ],

    // Meta Tags & SEO
    'meta' => [
        'title' => 'TopItUp 🎉 Count Everything!',
        'description' => 'Who said counting can\'t be fun? Track your beers, coffees, workouts, or literally anything. Compete with friends and see who\'s really winning at life 🏆',
        'keywords' => 'counter app, habit tracker, activity tracker, leaderboard, count drinks, track habits, pwa, productivity',
        'og_title' => 'TopItUp - Track What Matters',
        'og_description' => 'Beers? Gym sessions? Days without uni? Track anything, compete with friends, and actually have fun doing it. It\'s a party! 🎊',
    ],

    // Language Switcher
    'language' => [
        'name' => 'English',
        'code' => 'en',
        'switch_to' => 'Switch to Spanish',
    ],
];
