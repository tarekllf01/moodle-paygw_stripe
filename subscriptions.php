<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Subscription list page.
 *
 * @package    paygw_stripe
 * @author     Alex Morris <alex@navra.nz>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_stripe\stripe_helper;

require('../../../config.php');
require_once(__DIR__ . '/.extlib/stripe-php/init.php');

$action = optional_param('action', false, PARAM_TEXT);
$subid = optional_param('subscriptionid', null, PARAM_INT);

require_login();

$PAGE->set_url('/payment/gateway/stripe/subscriptions.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('subscriptions', 'paygw_stripe'));
$PAGE->set_heading(get_string('subscriptions', 'paygw_stripe'));

if (is_int($subid)) {
    $subscription = $DB->get_record('paygw_stripe_subscriptions', ['id' => $subid], '*', MUST_EXIST);
    $product = $DB->get_record('paygw_stripe_products', ['productid' => $subscription->productid]);
    $config = (object) helper::get_gateway_configuration($product->component, $product->paymentarea, $product->itemid, 'stripe');
    $stripehelper = new stripe_helper($config->apikey, $config->secretkey);

    if ($action == 'cancel') {
        $stripehelper->cancel_subscription($subscription);
        redirect(new moodle_url('/payment/gateway/stripe/subscriptions.php'));
    } else if ($action == 'portal') {
        $stripehelper->load_portal($subscription);
    }
}

echo $OUTPUT->header();

$table = new \html_table();
$table->head = [
    'Product',
    'Fee',
    'Scheduled Renewal',
    'Status',
    '',
    '',
];

$subscriptions = $DB->get_records('paygw_stripe_subscriptions', ['userid' => $USER->id]);

$table->data = [];

foreach ($subscriptions as $subscription) {
    $product = $DB->get_record('paygw_stripe_products', ['productid' => $subscription->productid]);
    $config = (object) helper::get_gateway_configuration($product->component, $product->paymentarea, $product->itemid, 'stripe');
    $stripehelper = new stripe_helper($config->apikey, $config->secretkey);
    $table->data[] = $stripehelper->get_subscription_table_data($subscription);
}

echo \html_writer::table($table);

echo $OUTPUT->footer();