#!/bin/bash
BASE_URL="http://localhost"

echo "Testing QR API endpoints..."

# Test QR generation endpoint
curl -s -o /dev/null -w "QR Code API: %{http_code}\n" \
    -X POST \
    -H "Content-Type: application/json" \
    -d '{"user_id":1,"action":"clock_in"}' \
    $BASE_URL/api/qr-codes/generate

# Test QR validate endpoint
curl -s -o /dev/null -w "QR Validate API: %{http_code}\n" \
    -X POST \
    -H "Content-Type: application/json" \
    -d '{"qr_code":"test123"}' \
    $BASE_URL/api/qr-codes/validate

# Check if QR view is accessible
curl -s -o /dev/null -w "QR View Page: %{http_code}\n" \
    $BASE_URL/frontend/src/views/admin/qr-code.php
