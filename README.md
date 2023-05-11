# mxlookup
Plugin for Roundcube that performs a lookup of the login user's MX records and dynamically chooses their imap_host from it.

The plugin was originally developed for MXroute, and there's one part in mxlookup.php that you can find specific to us:

            // Filter out any MX records containing the word "relay"
            $mx_records = array_filter($mx_records, function ($record) {
                return strpos($record['target'], 'relay') === false;
            });
            
Because what we wanted here was to pull the MX records for the login user's entered domain and subtract any records containing the string "relay" in them. At the least, this probably won't get in your way. At most, you might want to change it or remove it.

To install this plugin, clone the repo to your Roundcube plugin directory, add "mxlookup" to the array of enabled plugins in your config.inc.php, and in your config.inc.php you should set imap_host to a default value (Even just literally "default" is fine). You need to set imap_host to something or the user will see a "server" box on the login form that will interrupt your login flow.
