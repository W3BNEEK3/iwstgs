<?php
namespace Src\Simulation\Domain\Task;

enum DeliveryMode: string
{
    case Proactive = 'proactive';
    case Reactive  = 'reactive';
}
