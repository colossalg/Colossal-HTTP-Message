<?php declare(strict_types=1);

use Colossal\Http\Uri;
use PHPUnit\Framework\TestCase;

final class UriTest extends TestCase
{
    private Uri $uri;

    public function setUp(): void
    {
        $this->uri = new Uri;
    }

    public function testGetAuthority(): void
    {
        // The following test cases are formated as follows:
        //     - Key    => The expected string returned by getAuthority().
        //     - Value  => What parameters to set in the URI:
        //         - [0] => Whether to set the user info.
        //         - [1] => Whether to set the host.
        //         - [2] => Whether to set the port.
        // The combination of the user info, host and port determines what
        // getAuthority will return so we test all possible combinations.
        $testCases = [
            ""                                  => [false, false, false],
            ""                                  => [false, false, true ],
            "localhost"                         => [false, true,  false],
            "localhost:8080"                    => [false, true,  true ],
            ""                                  => [true,  false, false],
            ""                                  => [true,  false, true ],
            "root:password123@localhost"        => [true,  true,  false],
            "root:password123@localhost:8080"   => [true,  true,  true ]
        ];

        foreach ($testCases as $expected => $includes) {
            $newUri = $this->uri;
            if ($includes[0]) {
                $newUri = $newUri->withUserInfo("root", "password123");
            }
            if ($includes[1]) {
                $newUri = $newUri->withHost("localhost");
            }
            if ($includes[2]) {
                $newUri = $newUri->withPort(8080);
            }

            $this->assertEquals($expected, $newUri->getAuthority());
        }
    }

    public function testGetUserInfo(): void
    {
        // The following test cases are formatted as follows:
        //     - Key    => The expected string returned by getUserInfo().
        //     - Value  => What parameters to set in the URI:
        //         - [0] => Whether to set the user.
        //         - [1] => Whether to set the password.
        // The combination of the user and password determines what getUserInfo
        // will return so we test all possible combinations.
        $testCases = [
            ""                  => [false, false],
            ""                  => [false, true ],
            "root"              => [true,  false],
            "root:password123"  => [true,  true ]
        ];

        foreach ($testCases as $expected => $includes) {
            $user       = $includes[0] ? "root" : "";
            $password   = $includes[1] ? "password123" : null;

            $newUri = $this->uri->withUserInfo($user, $password);

            $this->assertEquals($expected, $newUri->getUserInfo());
        }
    }

    public function testGetPort(): void
    {
        // Test when there is no scheme and we set a port
        $newUri = $this->uri->withPort(8080);
        $this->assertEquals(8080, $newUri->getPort());

        // Test when there is no scheme and we don't set a port
        $newUri = $this->uri->withPort(null);
        $this->assertNull($newUri->getPort());

        // Test when there is a scheme and we set a non-default port for the scheme
        $newUri = $this->uri->withScheme("https");
        $newUri = $newUri->withPort(8080);
        $this->assertEquals(8080, $newUri->getPort());

        // Test when there is a scheme and we set a default port for the scheme
        $newUri = $this->uri->withScheme("https");
        $newUri = $newUri->withPort(443);
        $this->assertNull($newUri->getPort());

        // Test whent there is a scheme and we don't set a port
        $newUri = $this->uri->withScheme("https");
        $newUri = $newUri->withPort(null);
        $this->assertNull($newUri->getPort());
    }

    public function testWithScheme(): void
    {
        // Test that the method can set the supported schemes
        foreach (Uri::SUPPORTED_SCHEMES_AND_DEFAULT_PORTS as $scheme => $_) {
            $newUri = $this->uri->withScheme($scheme);
            $this->assertEquals("", $this->uri->getScheme());
            $this->assertEquals($scheme, $newUri->getScheme());
        }
    }

    public function testWithSchemeThrowsForNonStringSchemeArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'scheme'
        $this->expectException(\InvalidArgumentException::class);
        $this->uri->withScheme(1);
    }

    public function testWithSchemeThrowsForNonSupportedScheme(): void
    {
        // Test that the method throws an exception when we try to set a non-supported scheme
        $this->expectException(\InvalidArgumentException::class);
        $this->uri->withScheme("invalid");
    }

    public function testWithUserInfo(): void
    {
        // Test that the method works in the general user / password combinations
        
        $newUri = $this->uri->withUserInfo("root");
        $this->assertEquals("", $this->uri->getUserInfo());
        $this->assertEquals("root", $newUri->getUserInfo());

        $newUri = $this->uri->withUserInfo("root", "password123");
        $this->assertEquals("", $this->uri->getUserInfo());
        $this->assertEquals("root:password123", $newUri->getUserInfo());

        // Test that setting the user to empty string clears both the user and the password
        $newUri = $this->uri->withUserInfo("root", "password123");
        $newUri = $newUri->withUserInfo("", "should-not-be-set");
        $this->assertEquals("", $newUri->getUserInfo());
    }

    public function testWithUserInfoThrowsForNonStringUserArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'user'
        $this->expectException(\InvalidArgumentException::class);
        $this->uri->withUserInfo(1);
    }

    public function testWithUserInfoThrowsForNonNullOrStringPasswordArgument(): void
    {
        // Test that the method throws when we provide it with a non null or string value for the argument 'password'
        $this->expectException(\InvalidArgumentException::class);
        $this->uri->withUserInfo("root", 1);
    }

    public function testWithHost(): void
    {
        // Test that the method works in the general case
        $newUri = $this->uri->withHost("localhost");
        $this->assertEquals("", $this->uri->getHost());
        $this->assertEquals("localhost", $newUri->getHost());
    }

    public function testWithHostThrowsForNonStringHostArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'host'
        $this->expectException(\InvalidArgumentException::class);
        $this->uri->withHost(1);
    }

    public function testWithPort(): void
    {
        // Test that the method works in the general cases
        
        $newUri = $this->uri->withPort(1);
        $this->assertEquals(null, $this->uri->getPort());
        $this->assertEquals(1, $newUri->getPort());

        $newUri = $newUri->withPort(null);
        $this->assertNull($newUri->getPort());

    }

    public function testWithPortThrowsForNonIntOrNullPortArgument(): void
    {
        // Test that the method throws when we provide it with a non int or null value for the argument 'port'
        $this->expectException(\InvalidArgumentException::class);
        $this->uri->withPort("1");
    }

    public function testWithPortThrowsWhenViolatesLowerPortBound(): void
    {
        // Test that the method throws when we try to set a port outside of the TCP/UDP ranges
        $this->expectException(\InvalidArgumentException::class);
        $this->uri->withPort(Uri::TCP_LOWER_RANGE - 1);
    }

    public function testWithPortThrowsWhenViolatesUpperPortBound(): void
    {
        // Test that the method throws when we try to set a port outside of the TCP/UDP ranges
        $this->expectException(\InvalidArgumentException::class);
        $this->uri->withPort(Uri::TCP_UPPER_RANGE + 1);
    }

    public function testWithPath(): void
    {
        // Test that the method works in the general case
        $newUri = $this->uri->withPath("www.testing.com");
        $this->assertEquals("", $this->uri->getPath());
        $this->assertEquals("www.testing.com", $newUri->getPath());
    }

    public function testWithPathThrowsForNonStringPathArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'path'
        $this->expectException(\InvalidArgumentException::class);
        $this->uri->withPath(1);
    }

    public function testWithQuery(): void
    {
        // Test that the method works in the general case
        $newUri = $this->uri->withQuery("user=root&password=password123");
        $this->assertEquals("", $this->uri->getQuery());
        $this->assertEquals("user=root&password=password123", $newUri->getQuery());
    }

    public function testWithQueryThrowsForNonStringQueryArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'query'
        $this->expectException(\InvalidArgumentException::class);
        $this->uri->withQuery(1);
    }

    public function testWithFragment(): void
    {
        // Test that the method works in the general case
        $newUri = $this->uri->withFragment("title");
        $this->assertEquals("", $this->uri->getFragment());
        $this->assertEquals("title", $newUri->getFragment());
    }

    public function testWithFragmentThrowsForNonStringFragmentArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'fragment'
        $this->expectException(\InvalidArgumentException::class);
        $this->uri->withFragment(1);
    }

    public function testToString(): void
    {
        // The following test cases are formatted as follows:
        //     - Key    => The expected string returned by __toString().
        //     - Value  => What parameters to set in the URI:
        //          - [0] => The scheme.
        //          - [1] => The user component of the user info.
        //          - [2] => The password component of the user info.
        //          - [3] => The host.
        //          - [4] => The port.
        //          - [5] => The path.
        //          - [6] => The query.
        //          - [7] => The fragment.
        // All of these values influence what __toString() returns so we test a
        // bunch of combinations. In particular, we try to cover all of the
        // important combinations of the authority and path components as
        // detailed in the IUri::__toString() documentation.
        $testCases = [
            // Test each of the individual components on their own (scheme, authority, host, path, query, fragment)
            "http:"                                                         => ["http", "", "", "", null, "", "", ""],
            "http://authority"                                              => ["http", "", "", "authority", null, "", "", ""],
            "http:path"                                                     => ["http", "", "", "", null, "path", "", ""],
            "http:/path"                                                    => ["http", "", "", "", null, "/path", "", ""],
            "http:/path"                                                    => ["http", "", "", "", null, "//path", "", ""],
            "http:?query"                                                   => ["http", "", "", "", null, "", "query", ""],
            "http:#fragment"                                                => ["http", "", "", "", null, "", "", "fragment"],
            // Test some fairly generic looking web URLs
            "http://localhost:8080"                                         => ["http", "", "", "localhost", 8080, "", "", ""],
            "http://localhost:8080/"                                        => ["http", "", "", "localhost", 8080, "/", "", ""],
            "http://localhost:8080/index"                                   => ["http", "", "", "localhost", 8080, "/index", "", ""],
            "http://localhost:8080/index/"                                  => ["http", "", "", "localhost", 8080, "/index/", "", ""],
            "http://localhost:8080/users/1"                                 => ["http", "", "", "localhost", 8080, "/users/1", "", ""],
            "http://localhost:8080/users?id=1"                              => ["http", "", "", "localhost", 8080, "users", "id=1", ""],
            "http://localhost:8080/users?first_name=John&last_name=Doe"     => ["http", "", "", "localhost", 8080, "users", "first_name=John&last_name=Doe", ""],
            "http://localhost:8080/index#title"                             => ["http", "", "", "localhost", 8080, "index", "", "title"],
            "http://localhost:8080/users?id=1#profile"                      => ["http", "", "", "localhost", 8080, "users", "id=1", "profile"],
            "http://root:password123@localhost:8080/index"                  => ["http", "root", "password123", "localhost", 8080, "index", "", ""],
            "http://www.google.com"                                         => ["http", "", "", "www.google.com", null, "", "", ""],
            // Test the combinations of both authority and path (either on their own are already tested above)
            "http://authority/path"                                         => ["http", "", "", "authority", null, "path", "", ""],
            "http://authority/path"                                         => ["http", "", "", "authority", null, "/path", "", ""],
            "http://authority//path"                                        => ["http", "", "", "authority", null, "//path", "", ""],
            "http://authority///path"                                       => ["http", "", "", "authority", null, "///path", "", ""],
        ];

        foreach ($testCases as $expected => $components) {
            $newUri = $this->uri
                ->withScheme($components[0])
                ->withUserInfo($components[1], $components[2])
                ->withHost($components[3])
                ->withPort($components[4])
                ->withPath($components[5])
                ->withQuery($components[6])
                ->withFragment($components[7]);
            $this->assertEquals($expected, $newUri->__toString());
        }
    }
}