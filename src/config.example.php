<?php
// Kopieer dit bestand naar config.php en vul de gegevens van de Microsoft Entra-app in.
// Zet config.php nooit op GitHub.

const GRAPH_TENANT_ID = 'VUL_HIER_DE_TENANT_ID_IN';
const GRAPH_CLIENT_ID = 'VUL_HIER_DE_CLIENT_ID_IN';
const GRAPH_CLIENT_SECRET = 'VUL_HIER_DE_CLIENT_SECRET_IN';

// Mailbox van waaruit Microsoft Graph de mail verstuurt.
const GRAPH_SENDER_ADDRESS = 'verkoop@aertsactionbike.be';
const MAIL_FROM_NAME = 'Aerts Action Bike';
const MAIL_REPLY_TO = 'verkoop@aertsactionbike.be';

const BOOKING_URL = 'https://outlook.office365.com/book/Verkochtefietsen@aertsactionbike.be/?ismsaljsauthenabled=true';
