# NGINX

## Overview

Nginx is open-source web server that provides capabilities like reverse proxying, caching, load balancing, media streaming, and more. It started out as a web server designed for maximum performance and stability. In addition to its HTTP server capabilities, NGINX can also function as a proxy server for email (IMAP, POP3, and SMTP) and a reverse proxy and load balancer for HTTP/2, TCP, and UDP protocols.

## High Requests Handling Architecture

Nginx utilizes an event-driven architecture and deals with the requests asynchronously. It was designed to use a non-blocking event-driven connection handling algorithm. Hence, its process can handle thousands of connections (requests) within 1 processing thread. Such connections process modules allow Nginx to work very fast and wide with limited resources. Also, you can use Nginx to handle more than 10,000 simultaneous connections with low (CPU & Memory) resources under heavy request loads.

## Reverse proxy server include:

#### Load balancing 
A reverse proxy server can act as a “traffic cop,” sitting in front of your backend servers and distributing client requests across a group of servers in a manner that maximizes speed and capacity utilization while ensuring no one server is overloaded, which can degrade performance. If a server goes down, the load balancer redirects traffic to the remaining online servers.

#### Web acceleration 
Reverse proxies can compress inbound and outbound data, as well as cache commonly requested content, both of which speed up the flow of traffic between clients and servers. They can also perform additional tasks such as SSL encryption to take load off of your web servers, thereby boosting their performance.

#### Security and anonymity 
By intercepting requests headed for your backend servers, a reverse proxy server protects their identities and acts as an additional defense against security attacks. It also ensures that multiple servers can be accessed from a single record locator or URL regardless of the structure of your local area network.

#### Logging

Nginx provides centralized logging for backed server request and response passing through it and provides a single place to audit and log for troubleshooting issues.

#### TLS/SSL support 

Nginx allows secure communication between client and server using TLS/SSL connection. User data remains secure & encrypted while transferring over the wire using an HTTPS connection.