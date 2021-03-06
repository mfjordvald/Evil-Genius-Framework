# About

This framework is a proof-of-concept framework used in conjunction with Nginx full page caching. The idea is to be able to cache full pages without accepting stale data in the cache. To do this all data is tracked using framework-provided methods such that when data is updated the relevant cache keys are deleted and a new version can be fetched on next request.

# Relevant files

### /system/core/cachetracker.php

This file contains the tracking methods and defines the interfaces for controllers to implement when they manipulate data.

### /app/controllers/news.php

Sample controller that does a basic task (news/comment reading) and reports data keys that it reads as well as which URI(cache key) uses this data.

### /app/libraries/cachetest/news.php

Sample library that does a basic task (news/comment posting) and reports data keys that it writes to as well as trigger an invalidation request for that data key when the data is updated.

# References

http://blog.martinfjordvald.com/2010/09/12000-requests-per-second-with-nginx-php-and-memcached/ was the first blog post that dealt with the theory behind full page caching, how to deal with stale caches and limitations on invalidating cache keys based on data updated.

http://blog.martinfjordvald.com/2011/02/implementing-full-page-caching-with-nginx-and-php/ deals with the technical aspects of implementing this framework and configures nginx to fetch cached pages.

Part 3 deals with how to handle dynamic data in fully cached pages, for example pages containing user data. This part is currently on my todo list under "Soon. Maybe." which is developer speak for "ehhhhhh".