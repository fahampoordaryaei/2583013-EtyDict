![EtyDict Title](https://i.nuuls.com/YBkKz.png)

**The free online dictionary and thesaurus explorer.**

EtyDict is a free online dictionary and etymology browser powered by a rich collection of dictionary and etymological data.

**Current dataset status**: 158,664 definitions for 103,831 words and phrases.

# Features
 - Advanced multi-dimensional search
 - Rich database
 - Dynamic etymology graph powered by Wiktionary data
 - Account and profile for multi-device sync
 - Word favoriting and history tracking
 - Popular and trending words
 - User browsing data for analytics interests 

# Requirements

 - Composer
	- aws-sdk-php
	 - twig

 - XAMPP
	 -	Apache
	 -	MySQL

# Installation (Windows)
## Pre-installation
**To ensure the website works correctly, create etydict.com as a VirtualHost in XAMPP and add the address to your hosts file:**

 1. Ensure XAMPP is listening to port 80 (it is by default)
 2. Extract etydict to C:\xampp\htdocs
 3.  Add etydict.com as a virtualhost in C:\xampp\apache\conf\extra\httpd-vhosts.conf
 
    <VirtualHost *:80>
    	DocumentRoot "C:/XAMPP/htdocs/etydict/public/"
    	ServerName etydict.com
	</VirtualHost>

4. Add this line to your hosts file at C:\Windows\System32\drivers\etc\hosts

		127.0.0.1 etydict.com  

- **NOTE:** If you input your own website name, change the link address in the email templates (templates/email/) as they are hard-coded. The website does not have any other issues with different addresses.

### Security

 For security purposes, I recommend making the following changes in Apache configuration:
 - disable exposing /server-status in C:\xampp\apache\conf\httpd.conf:

Right below

    ServerRoot "C:/xampp/apache"
Add

    <Location "/server-status">
    	Require all denied
	</Location>

	LoadModule headers_module modules/mod_headers.so
	ServerTokens Prod
	
	ServerSignature Off


And comment out (add # to) these lines

    Include conf/extra/httpd-info.conf
    LoadModule status_module modules/mod_status.so

 - In  C:\xampp\apache\conf\extra\httpd.conf

Replace

    ServerTokens Full

with

    ServerTokens Prod

## Setup
1. Install composer requirements by running this command in the etydict folder

		composer install
    
2.  The website uses mySQL for its DBMS. Create table `dictionary` and import dictionary.sql into it (please be patient).
3. **RECOMMENDED:** Import sample_views.sql to have some view data for Trending Words function. It contains view data with no user-account associations.
4. Extract etydict/src/config.zip using the provided password. If needed, replace mySQL credentials in db.php with your own.

# ZAP Report *(for Assessment purposes)*
ZAP report can be found in the report folder.
Some prominent medium-risk "vulnerabilities" described in the report are explained below.

## Explanation of risks
### Anti-CSRF
The "absence of Anti-CSRF" occurs as a result of forms having anti-CSRF tokens embedded in them, but no validation occuring on POST. All other end-points already generate and validate the tokens.

**UPDATE:** All forms now validate tokens.

### Content Security Policy
CSP warnings are a trade-off of having inline scripts. In general, it is not high risk and partially expected.
Static pages do not require a CSP header, even though ZAP still considers it as such.
For further security, explicit elements and API endpoints where whitelisted/allowed for the CSP.

### Cross-domain misconfiguration
Some cross-domain misconfig is linked to third-party scripts including reCAPTCHA and Bootstrap. The other instances of cross-domain misconfig do not concern sensitive data.

### Cross-domain Javascript source file inclusion
Triggered by the instance of reCAPTCHA script.

# Contact
You can reach out to me on email: Fahamp@stcmalta.edu.mt

# Credits
- EtyTree Etymology API: https://github.com/agmmnn/etytree
- Vis.js for graph visualisation: https://github.com/visjs/vis-network
- Google reCAPTCHA v3
- Mr Steven Camilleri, and Illy Classico Espresso Roast -- in no particular order
# License
The MIT License (MIT)

Copyright © 2025 


Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the “Software”), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

:)

