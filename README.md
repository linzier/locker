Redis Locker
----
A distributed lock implementation based on Redis, primarily using `setnx`.

Note: This is a simplified implementation of a distributed lock, which covers the majority of business use cases. For the complete implementation principles of Redis distributed locks, refer to the blog post:

[A Progressive Guide to Redis Distributed Locks](https://linzier.github.io/blogs/redis-distributed-lock.html)

References:
[Redis Official Documentation](https://redis.io/topics/distlock)

