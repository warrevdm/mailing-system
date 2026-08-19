<?php

// Microsoft Entra / Microsoft Graph
const MS_TENANT_ID = 'VUL_HIER_TENANT_ID_IN';
const MS_CLIENT_ID = 'VUL_HIER_CLIENT_ID_IN';
const MS_CLIENT_SECRET = 'VUL_HIER_CLIENT_SECRET_IN';

// Microsoft Graph instellingen
const MS_GRAPH_BASE_URL = 'https://graph.microsoft.com/v1.0';
const MS_GRAPH_SCOPE = 'https://graph.microsoft.com/.default';

// Afzender
const MAIL_FROM_ADDRESS = 'verkoop@aertsactionbike.be';
const MAIL_FROM_NAME = 'Aerts Action Bike';
const MAIL_REPLY_TO = 'verkoop@aertsactionbike.be';

// Rechtstreeks verzenden via Microsoft Graph
const INTERNAL_SEND_ENABLED = true;

// Outlook .eml als fallback wanneer Graph niet kan verzenden
const EML_FALLBACK_ENABLED = true;

// Link waarmee klanten een afhaalmoment voor hun nieuwe fiets boeken.
const BOOKING_URL = 'https://outlook.office365.com/book/Verkochtefietsen@aertsactionbike.be/?ismsaljsauthenabled=true';
