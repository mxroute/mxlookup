# mxlookup Plugin for Roundcube

The mxlookup plugin for Roundcube performs a lookup of the login user's MX records and dynamically chooses the `imap_host` based on the retrieved MX records. This allows for flexible configuration of the IMAP host based on the user's domain.

## Features

- Retrieves the MX records for the user's domain
- Filters out MX records containing the word "relay"
- Selects the lowest priority MX record as the `imap_host` value
- Validates the `imap_host` IP address against a whitelist

## Requirements

- Roundcube webmail version X.X.X or higher

## Installation

1. Clone the mxlookup plugin repository into your Roundcube plugins directory:

    git clone https://github.com/your-username/mxlookup.git plugins/mxlookup
    
2. Add "mxlookup" to the plugins array in your Roundcube config/config.inc.php file:

    $config['plugins'] = array('mxlookup');

3. In the same config/config.inc.php file, set the imap_host configuration option to a default value. For example:

    $config['imap_host'] = 'default';

This ensures that the user does not see a "server" box on the login form.

4. Create a whitelist.txt file in the plugins/mxlookup directory. This file should contain a list of approved IP addresses (one per line) that are allowed to be used as imap_host values. This whitelist prevents the plugin from being utilized by users on unauthorized servers. Example whitelist.txt file:

    192.168.0.10
    10.0.0.5

## Customization

- Filtering MX Records

The original mxlookup plugin was developed for MXroute. If you need to modify the filtering of MX records, you can adjust the following code snippet in plugins/mxlookup/mxlookup.php:

    // Filter out any MX records containing the word "relay"
    $mx_records = array_filter($mx_records, function ($record) {
        return strpos($record['target'], 'relay') === false;
    });

You can modify or remove this code snippet based on your specific requirements.
